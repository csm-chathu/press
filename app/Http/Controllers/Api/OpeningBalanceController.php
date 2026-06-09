<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Product;
use App\Support\StockLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpeningBalanceController extends Controller
{
    // GET /api/opening-balance/stock
    public function stockIndex(Request $request)
    {
        $user = $request->user();
        $products = Product::with('category:id,name')
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'category_id', 'stock_quantity', 'unit_type', 'branch_id']);

        return response()->json($products);
    }

    // POST /api/opening-balance/stock
    public function stockSave(Request $request)
    {
        $request->validate([
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);

                if (!$request->user()->isAdmin() && $product->branch_id !== $request->user()->branch_id) {
                    continue;
                }

                $newQty = (float) $item['quantity'];
                $product->stock_quantity = $newQty;
                $product->save();
                $product->refresh();

                StockLedger::record(
                    $product,
                    'IN',
                    $newQty,
                    $request->user()->id,
                    $product->branch_id,
                    'OPENING',
                    null,
                    'Opening balance',
                );
            }

            DB::commit();
            return response()->json(['message' => 'Stock opening balances saved.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // GET /api/opening-balance/accounts
    public function accountIndex()
    {
        $accounts = Account::whereNull('branch_id')
            ->orWhere('branch_id', request()->user()->branch_id)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type']);

        return response()->json($accounts);
    }

    // POST /api/opening-balance/accounts
    public function accountSave(Request $request)
    {
        $request->validate([
            'items'             => 'required|array|min:1',
            'items.*.account_id'=> 'required|exists:accounts,id',
            'items.*.debit'     => 'nullable|numeric|min:0',
            'items.*.credit'    => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $entry = JournalEntry::create([
                'branch_id'    => $request->user()->branch_id,
                'user_id'      => $request->user()->id,
                'source_type'  => 'OPENING',
                'source_id'    => 0,
                'description'  => 'Opening balances',
                'posted_at'    => now(),
            ]);

            foreach ($request->items as $item) {
                $debit  = (float) ($item['debit']  ?? 0);
                $credit = (float) ($item['credit'] ?? 0);
                if ($debit <= 0 && $credit <= 0) continue;

                JournalLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id'       => $item['account_id'],
                    'debit'            => $debit,
                    'credit'           => $credit,
                    'description'      => 'Opening balance',
                ]);
            }

            DB::commit();
            return response()->json(['message' => 'Account opening balances saved.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
