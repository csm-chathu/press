<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountingController extends Controller
{
    public function journalEntries(Request $request)
    {
        $user = $request->user();
        $from = $request->date_from ?? now()->startOfMonth()->toDateString();
        $to = $request->date_to ?? now()->toDateString();

        $query = JournalEntry::with(['lines.account:id,code,name,type', 'user:id,name'])
            ->where('status', 'posted')
            ->whereBetween('entry_date', [$from, $to])
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->orderByDesc('entry_date')
            ->orderByDesc('id');

        return response()->json([
            'from' => $from,
            'to' => $to,
            'entries' => $query->paginate($request->per_page ?? 30),
        ]);
    }

    public function trialBalance(Request $request)
    {
        $user = $request->user();
        $from = $request->date_from ?? now()->startOfMonth()->toDateString();
        $to = $request->date_to ?? now()->toDateString();

        $rows = DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->where('journal_entries.status', 'posted')
            ->whereBetween('journal_entries.entry_date', [$from, $to])
            ->when(!$user->isAdmin(), fn($q) => $q->where('journal_entries.branch_id', $user->branch_id))
            ->select(
                'accounts.id',
                'accounts.code',
                'accounts.name',
                'accounts.type',
                DB::raw('SUM(journal_lines.debit) as debit'),
                DB::raw('SUM(journal_lines.credit) as credit')
            )
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type')
            ->orderBy('accounts.code')
            ->get();

        return response()->json([
            'from' => $from,
            'to' => $to,
            'accounts' => $rows,
            'totals' => [
                'debit' => round((float) $rows->sum('debit'), 2),
                'credit' => round((float) $rows->sum('credit'), 2),
            ],
        ]);
    }

    public function profitLoss(Request $request)
    {
        $user = $request->user();
        $from = $request->date_from ?? now()->startOfMonth()->toDateString();
        $to = $request->date_to ?? now()->toDateString();

        $rows = DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->where('journal_entries.status', 'posted')
            ->whereIn('accounts.type', ['revenue', 'expense'])
            ->whereBetween('journal_entries.entry_date', [$from, $to])
            ->when(!$user->isAdmin(), fn($q) => $q->where('journal_entries.branch_id', $user->branch_id))
            ->select(
                'accounts.id',
                'accounts.code',
                'accounts.name',
                'accounts.type',
                DB::raw('SUM(journal_lines.debit) as debit'),
                DB::raw('SUM(journal_lines.credit) as credit')
            )
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type')
            ->orderBy('accounts.code')
            ->get()
            ->map(function ($row) {
                $debit = (float) $row->debit;
                $credit = (float) $row->credit;
                $amount = $row->type === 'revenue' ? ($credit - $debit) : ($debit - $credit);
                $row->amount = round($amount, 2);
                return $row;
            });

        $revenues = $rows->where('type', 'revenue')->values();
        $expenses = $rows->where('type', 'expense')->values();

        $totalRevenue = round((float) $revenues->sum('amount'), 2);
        $totalExpense = round((float) $expenses->sum('amount'), 2);

        return response()->json([
            'from' => $from,
            'to' => $to,
            'revenues' => $revenues,
            'expenses' => $expenses,
            'summary' => [
                'total_revenue' => $totalRevenue,
                'total_expense' => $totalExpense,
                'net_profit' => round($totalRevenue - $totalExpense, 2),
            ],
        ]);
    }

    public function balanceSheet(Request $request)
    {
        $user = $request->user();
        $asOf = $request->date ?? now()->toDateString();

        $rows = DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->where('journal_entries.status', 'posted')
            ->whereIn('accounts.type', ['asset', 'liability', 'equity'])
            ->whereDate('journal_entries.entry_date', '<=', $asOf)
            ->when(!$user->isAdmin(), fn($q) => $q->where('journal_entries.branch_id', $user->branch_id))
            ->select(
                'accounts.id',
                'accounts.code',
                'accounts.name',
                'accounts.type',
                DB::raw('SUM(journal_lines.debit) as debit'),
                DB::raw('SUM(journal_lines.credit) as credit')
            )
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type')
            ->orderBy('accounts.code')
            ->get()
            ->map(function ($row) {
                $debit = (float) $row->debit;
                $credit = (float) $row->credit;
                $row->balance = $row->type === 'asset'
                    ? round($debit - $credit, 2)
                    : round($credit - $debit, 2);
                return $row;
            });

        $assets = $rows->where('type', 'asset')->values();
        $liabilities = $rows->where('type', 'liability')->values();
        $equity = $rows->where('type', 'equity')->values();

        return response()->json([
            'as_of' => $asOf,
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'totals' => [
                'assets' => round((float) $assets->sum('balance'), 2),
                'liabilities' => round((float) $liabilities->sum('balance'), 2),
                'equity' => round((float) $equity->sum('balance'), 2),
            ],
        ]);
    }
}
