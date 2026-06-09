<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeSalaryPayment;
use App\Models\IncomeExpense;
use App\Support\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class FinanceController extends Controller
{
    public function employees(Request $request)
    {
        $user = $request->user();
        $query = Employee::query()
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($request->filled('is_active'), fn($q) => $q->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim((string) $request->search);
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderBy('name');

        return response()->json($query->paginate((int) ($request->per_page ?? 20)));
    }

    public function storeEmployee(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'employee_code' => 'nullable|string|max:40',
            'name' => 'required|string|max:120',
            'role' => 'nullable|string|max:120',
            'phone' => 'nullable|string|max:60',
            'base_salary' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $employee = Employee::create([
            'branch_id' => $user->branch_id,
            'employee_code' => $data['employee_code'] ?? null,
            'name' => $data['name'],
            'role' => $data['role'] ?? null,
            'phone' => $data['phone'] ?? null,
            'base_salary' => (float) ($data['base_salary'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return response()->json($employee, 201);
    }

    public function updateEmployee(Request $request, Employee $employee)
    {
        $user = $request->user();
        if (!$user->isAdmin() && $employee->branch_id !== $user->branch_id) {
            abort(403, 'Forbidden for this branch.');
        }

        $data = $request->validate([
            'employee_code' => 'nullable|string|max:40',
            'name' => 'sometimes|string|max:120',
            'role' => 'nullable|string|max:120',
            'phone' => 'nullable|string|max:60',
            'base_salary' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $employee->update($data);

        return response()->json($employee->fresh());
    }

    public function salaryPayments(Request $request)
    {
        $user = $request->user();
        $from = $request->date_from ?? now()->startOfMonth()->toDateString();
        $to = $request->date_to ?? now()->toDateString();

        $query = EmployeeSalaryPayment::with(['employee:id,name,employee_code', 'user:id,name'])
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->whereBetween('paid_at', [$from, $to])
            ->when($request->filled('employee_id'), fn($q) => $q->where('employee_id', $request->employee_id))
            ->latest('paid_at');

        return response()->json($query->paginate((int) ($request->per_page ?? 20)));
    }

    public function paySalary(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'period_month' => 'required|date',
            'paid_at' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|max:40',
            'reference' => 'nullable|string|max:120',
            'notes' => 'nullable|string',
        ]);

        $employee = Employee::findOrFail($data['employee_id']);
        if (!$user->isAdmin() && $employee->branch_id !== $user->branch_id) {
            abort(403, 'Forbidden for this branch.');
        }

        $salaryPayment = EmployeeSalaryPayment::create([
            'branch_id' => $employee->branch_id,
            'employee_id' => $employee->id,
            'period_month' => Carbon::parse($data['period_month'])->startOfMonth()->toDateString(),
            'paid_at' => Carbon::parse($data['paid_at'])->toDateString(),
            'amount' => (float) $data['amount'],
            'payment_method' => $data['payment_method'] ?? 'cash',
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
            'user_id' => $user->id,
        ]);

        $journal = AccountingService::post(
            sourceType: 'SALARY',
            sourceId: $salaryPayment->id,
            branchId: (int) $salaryPayment->branch_id,
            userId: (int) $user->id,
            entryDate: $salaryPayment->paid_at,
            reference: $salaryPayment->reference ?: 'SAL-' . $salaryPayment->id,
            description: 'Salary payment - ' . $employee->name,
            lines: [
                ['code' => AccountingService::ACC_SALARY_EXP, 'debit' => (float) $salaryPayment->amount, 'credit' => 0, 'memo' => 'Salary expense'],
                ['code' => AccountingService::ACC_CASH, 'debit' => 0, 'credit' => (float) $salaryPayment->amount, 'memo' => 'Salary paid'],
            ],
            meta: [
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'period_month' => $salaryPayment->period_month?->toDateString(),
                'payment_method' => $salaryPayment->payment_method,
            ]
        );

        if ($journal) {
            $salaryPayment->update(['journal_entry_id' => $journal->id]);
        }

        return response()->json($salaryPayment->fresh()->load('employee:id,name,employee_code', 'user:id,name'), 201);
    }

    public function incomeExpenses(Request $request)
    {
        $user = $request->user();
        $from = $request->date_from ?? now()->startOfMonth()->toDateString();
        $to = $request->date_to ?? now()->toDateString();

        $query = IncomeExpense::with('user:id,name')
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->whereBetween('entry_date', [$from, $to])
            ->when($request->filled('type'), fn($q) => $q->where('type', $request->type))
            ->latest('entry_date');

        return response()->json($query->paginate((int) ($request->per_page ?? 20)));
    }

    public function storeIncomeExpense(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'type' => 'required|in:income,expense',
            'entry_date' => 'required|date',
            'category' => 'nullable|string|max:120',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|max:40',
            'reference' => 'nullable|string|max:120',
            'account_code' => 'nullable|string|max:30',
            'notes' => 'nullable|string',
        ]);

        $entry = IncomeExpense::create([
            'branch_id' => $user->branch_id,
            'type' => $data['type'],
            'entry_date' => Carbon::parse($data['entry_date'])->toDateString(),
            'category' => $data['category'] ?? null,
            'description' => $data['description'],
            'amount' => (float) $data['amount'],
            'payment_method' => $data['payment_method'] ?? 'cash',
            'reference' => $data['reference'] ?? null,
            'account_code' => $data['account_code'] ?? null,
            'notes' => $data['notes'] ?? null,
            'user_id' => $user->id,
        ]);

        $amount = (float) $entry->amount;
        $isIncome = $entry->type === 'income';
        $targetCode = $entry->account_code
            ?: ($isIncome ? AccountingService::ACC_OTHER_INCOME : AccountingService::ACC_OPERATING_EXP);

        $journal = AccountingService::post(
            sourceType: 'INCOME_EXPENSE',
            sourceId: $entry->id,
            branchId: (int) $entry->branch_id,
            userId: (int) $user->id,
            entryDate: $entry->entry_date,
            reference: $entry->reference,
            description: ($isIncome ? 'Income' : 'Expense') . ' - ' . $entry->description,
            lines: $isIncome
                ? [
                    ['code' => AccountingService::ACC_CASH, 'debit' => $amount, 'credit' => 0, 'memo' => 'Cash received'],
                    ['code' => $targetCode, 'debit' => 0, 'credit' => $amount, 'memo' => 'Other income'],
                ]
                : [
                    ['code' => $targetCode, 'debit' => $amount, 'credit' => 0, 'memo' => 'Expense recognized'],
                    ['code' => AccountingService::ACC_CASH, 'debit' => 0, 'credit' => $amount, 'memo' => 'Cash paid'],
                ],
            meta: [
                'type' => $entry->type,
                'category' => $entry->category,
                'payment_method' => $entry->payment_method,
            ]
        );

        if ($journal) {
            $entry->update(['journal_entry_id' => $journal->id]);
        }

        return response()->json($entry->fresh()->load('user:id,name'), 201);
    }
}
