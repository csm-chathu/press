<?php

namespace App\Support;

use App\Models\Account;
use App\Models\DamageReport;
use App\Models\Grn;
use App\Models\JournalEntry;
use App\Models\Sale;
use App\Models\SupplierReturn;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class AccountingService
{
    public const ACC_CASH = '1000';
    public const ACC_AR = '1100';
    public const ACC_INVENTORY = '1200';
    public const ACC_AP = '2000';
    public const ACC_TAX_PAYABLE = '2100';
    public const ACC_BOTTLE_DEPOSIT = '2200';
    public const ACC_SALES = '4000';
    public const ACC_SALES_DISCOUNT = '4050';
    public const ACC_OTHER_INCOME = '4100';
    public const ACC_COGS = '5000';
    public const ACC_DAMAGE_EXP = '5100';
    public const ACC_SALARY_EXP = '5200';
    public const ACC_OPERATING_EXP = '5300';

    public static function postSale(Sale $sale): ?JournalEntry
    {
        if ($sale->status !== 'completed') {
            return null;
        }

        $total = round((float) $sale->total, 2);
        if ($total <= 0) {
            return null;
        }

        $tax      = max(0, round((float) $sale->tax, 2));
        $discount = max(0, round((float) $sale->discount, 2));
        // Gross revenue = net revenue + discount (total − tax is net; add back discount for gross)
        $netRevenue   = max(0, round($total - $tax, 2));
        $grossRevenue = max(0, round($netRevenue + $discount, 2));
        $paid         = max(0, min($total, round((float) ($sale->amount_paid ?? 0), 2)));
        $receivable   = max(0, round($total - $paid, 2));

        $cogs = round((float) $sale->items()->with('product:id,purchase_price,base_unit')->get()
            ->sum(function ($item) {
                $qty           = (float) $item->quantity;
                $purchasePrice = (float) ($item->product?->purchase_price ?? 0);
                $servingMl     = (float) ($item->serving_ml ?? 0);

                if ($servingMl > 0 && $item->product?->base_unit) {
                    preg_match('/([0-9]+(?:\.[0-9]+)?)\s*ml/i', (string) $item->product->base_unit, $m);
                    $baseMl = isset($m[1]) ? (float) $m[1] : 0;
                    if ($baseMl > 0) {
                        return $qty * ($servingMl / $baseMl) * $purchasePrice;
                    }
                }

                return $qty * $purchasePrice;
            }), 2);

        $lines = [];

        if ($paid > 0) {
            $lines[] = ['code' => self::ACC_CASH, 'debit' => $paid, 'credit' => 0, 'memo' => 'Amount collected'];
        }
        if ($receivable > 0) {
            $lines[] = ['code' => self::ACC_AR, 'debit' => $receivable, 'credit' => 0, 'memo' => 'Outstanding amount'];
        }
        if ($discount > 0) {
            $lines[] = ['code' => self::ACC_SALES_DISCOUNT, 'debit' => $discount, 'credit' => 0, 'memo' => 'Sales discount granted'];
        }
        if ($grossRevenue > 0) {
            $lines[] = ['code' => self::ACC_SALES, 'debit' => 0, 'credit' => $grossRevenue, 'memo' => 'Gross sales revenue'];
        }
        if ($tax > 0) {
            $lines[] = ['code' => self::ACC_TAX_PAYABLE, 'debit' => 0, 'credit' => $tax, 'memo' => 'Tax payable'];
        }

        if ($cogs > 0) {
            $lines[] = ['code' => self::ACC_COGS, 'debit' => $cogs, 'credit' => 0, 'memo' => 'Cost of goods sold'];
            $lines[] = ['code' => self::ACC_INVENTORY, 'debit' => 0, 'credit' => $cogs, 'memo' => 'Inventory issued'];
        }

        return self::post(
            sourceType: 'SALE',
            sourceId: $sale->id,
            branchId: (int) $sale->branch_id,
            userId: (int) $sale->user_id,
            entryDate: $sale->sold_at,
            reference: $sale->invoice_number,
            description: 'Auto-posted sale journal',
            lines: $lines,
            meta: [
                'payment_method' => $sale->payment_method,
                'payment_status' => $sale->payment_status,
            ]
        );
    }

    public static function postGrn(Grn $grn): ?JournalEntry
    {
        $total = round((float) $grn->total, 2);
        if ($total <= 0) {
            return null;
        }

        return self::post(
            sourceType: 'GRN',
            sourceId: $grn->id,
            branchId: (int) $grn->branch_id,
            userId: (int) $grn->user_id,
            entryDate: $grn->received_at,
            reference: $grn->grn_number,
            description: 'Auto-posted GRN journal',
            lines: [
                ['code' => self::ACC_INVENTORY, 'debit' => $total, 'credit' => 0, 'memo' => 'Stock received'],
                ['code' => self::ACC_AP, 'debit' => 0, 'credit' => $total, 'memo' => 'Supplier payable'],
            ]
        );
    }

    public static function postSupplierReturn(SupplierReturn $supplierReturn): ?JournalEntry
    {
        $total = round((float) $supplierReturn->total_amount, 2);
        if ($total <= 0) {
            return null;
        }

        return self::post(
            sourceType: 'SUPPLIER_RETURN',
            sourceId: $supplierReturn->id,
            branchId: (int) $supplierReturn->branch_id,
            userId: (int) $supplierReturn->user_id,
            entryDate: $supplierReturn->returned_at,
            reference: $supplierReturn->return_number,
            description: 'Auto-posted supplier return journal',
            lines: [
                ['code' => self::ACC_AP, 'debit' => $total, 'credit' => 0, 'memo' => 'Payable reduced by return'],
                ['code' => self::ACC_INVENTORY, 'debit' => 0, 'credit' => $total, 'memo' => 'Inventory returned to supplier'],
            ]
        );
    }

    public static function postDamage(DamageReport $damageReport): ?JournalEntry
    {
        $loss = round((float) $damageReport->estimated_loss, 2);
        if ($loss <= 0) {
            return null;
        }

        return self::post(
            sourceType: 'DAMAGE',
            sourceId: $damageReport->id,
            branchId: (int) $damageReport->branch_id,
            userId: (int) $damageReport->user_id,
            entryDate: $damageReport->occurred_at,
            reference: $damageReport->reference_number,
            description: 'Auto-posted damage journal',
            lines: [
                ['code' => self::ACC_DAMAGE_EXP, 'debit' => $loss, 'credit' => 0, 'memo' => 'Damage write-off'],
                ['code' => self::ACC_INVENTORY, 'debit' => 0, 'credit' => $loss, 'memo' => 'Inventory write-down'],
            ]
        );
    }

    public static function post(
        string $sourceType,
        int $sourceId,
        int $branchId,
        int $userId,
        $entryDate,
        ?string $reference,
        string $description,
        array $lines,
        array $meta = []
    ): ?JournalEntry {
        if (!Schema::hasTable('accounts') || !Schema::hasTable('journal_entries') || !Schema::hasTable('journal_lines')) {
            return null;
        }

        if (empty($lines)) {
            return null;
        }

        $existing = JournalEntry::where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->first();

        if ($existing) {
            return $existing;
        }

        $prepared = [];
        try {
            foreach ($lines as $line) {
                $debit = round((float) ($line['debit'] ?? 0), 2);
                $credit = round((float) ($line['credit'] ?? 0), 2);
                if ($debit <= 0 && $credit <= 0) {
                    continue;
                }

                $prepared[] = [
                    'account_id' => self::resolveAccountId($line['code'], $branchId),
                    'debit' => $debit,
                    'credit' => $credit,
                    'memo' => $line['memo'] ?? null,
                ];
            }
        } catch (\Throwable $e) {
            report($e);
            return null;
        }

        if (empty($prepared)) {
            return null;
        }

        $totalDebit = round(collect($prepared)->sum('debit'), 2);
        $totalCredit = round(collect($prepared)->sum('credit'), 2);

        if ($totalDebit <= 0 || abs($totalDebit - $totalCredit) > 0.01) {
            throw new \RuntimeException('Journal is not balanced for source ' . $sourceType . ':' . $sourceId);
        }

        $entry = JournalEntry::create([
            'branch_id' => $branchId,
            'entry_date' => Carbon::parse($entryDate ?? now())->toDateString(),
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'reference' => $reference,
            'description' => $description,
            'status' => 'posted',
            'created_by' => $userId,
            'meta' => $meta,
        ]);

        $entry->lines()->createMany($prepared);

        return $entry;
    }

    private static function resolveAccountId(string $code, int $branchId): int
    {
        $account = Account::query()
            ->where('code', $code)
            ->where(function ($q) use ($branchId) {
                $q->whereNull('branch_id')
                    ->orWhere('branch_id', $branchId);
            })
            ->orderByRaw('CASE WHEN branch_id IS NULL THEN 1 ELSE 0 END')
            ->first();

        if (!$account) {
            throw new \RuntimeException('Account not found for code: ' . $code);
        }

        return (int) $account->id;
    }
}
