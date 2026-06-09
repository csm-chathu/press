<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BottleDeposit;
use App\Models\Customer;
use App\Models\GoldRate;
use App\Models\OpenBottle;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Support\AccountingService;
use App\Support\StockLedger;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    private const DEFAULT_BOTTLE_DEPOSIT = 100.0;

    public function index(Request $request)
    {
        $user = $request->user();
        $sales = Sale::with(['customer:id,name', 'user:id,name', 'payments:id,sale_id,payment_method,amount'])
            ->when(!$user->isAdmin(), fn($q) => $q->where('branch_id', $user->branch_id))
            ->when($request->filled('search'), fn($q) => $q->where('invoice_number', 'like', '%' . $request->string('search') . '%'))
            ->when($request->filled('customer_id'), fn($q) => $q->where('customer_id', $request->input('customer_id')))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('payment_status'), fn($q) => $q->where('payment_status', $request->input('payment_status')))
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('sold_at', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('sold_at', '<=', $request->input('date_to')))
            ->latest('sold_at')
            ->paginate($request->integer('per_page', 20));
        return response()->json($sales);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id'              => 'nullable|exists:customers,id',
            'items'                    => 'required|array|min:1',
            'items.*.product_id'       => 'required|exists:products,id',
            'items.*.quantity'         => 'required|integer|min:1',
            'items.*.unit_price'       => 'required|numeric|min:0',
            'items.*.discount'         => 'nullable|numeric|min:0',
            'items.*.empty_bottle_returned' => 'nullable|boolean',
            'items.*.bottle_deposit_amount' => 'nullable|numeric|min:0',
            'items.*.serving_ml'       => 'nullable|numeric|min:0',
            'items.*.open_bottle_id'   => 'nullable|exists:open_bottles,id',
            'discount'                 => 'nullable|numeric|min:0',
            'tax'                      => 'nullable|numeric|min:0',
            'tax_rate'                 => 'nullable|numeric|min:0|max:100',
            'payment_method'           => 'required_without:payments|in:cash,card,bank_transfer,cheque,other',
            'payment_status'           => 'nullable|in:pending,paid,partial,refunded',
            'amount_paid'              => 'nullable|numeric|min:0',
            'payments'                 => 'nullable|array|min:1',
            'payments.*.payment_method'=> 'required_with:payments|in:cash,card,bank_transfer,cheque,other',
            'payments.*.amount'        => 'required_with:payments|numeric|min:0.01',
            'payments.*.notes'         => 'nullable|string|max:255',
            'status'                   => 'nullable|in:draft,completed',
            'table_number'             => 'nullable|string|max:50',
            'card_reference'           => 'nullable|string|max:100',
            'notes'                    => 'nullable|string',
            'sold_at'                  => 'nullable|date',
        ]);

        $isDraft = ($data['status'] ?? 'completed') === 'draft';

        DB::beginTransaction();
        try {
            $goldRate  = GoldRate::today();
            $subtotal  = 0;
            $goldTotal = 0; $gemTotal = 0; $mcTotal = 0; $wasteTotal = 0;

            // Pre-validate items
            $itemData = [];
            foreach ($data['items'] as $item) {
                $product = Product::with('category')->findOrFail($item['product_id']);
                $isOpenBottleSell = !empty($item['open_bottle_id']);
                $isPourSale = !empty($item['serving_ml']) && $item['serving_ml'] > 0;
                if (!$isOpenBottleSell && !$isPourSale && $product->isStockTracked() && $product->stock_quantity < $item['quantity']) {
                    throw new \Exception("Insufficient stock for: {$product->name}");
                }

                $qty         = $item['quantity'];
                $unitPrice   = $item['unit_price'];
                $itemDisc    = $item['discount'] ?? 0;

                // Making charge
                $mc = $item['making_charge'] ?? 0;
                if ($mc == 0 && $product->making_charge > 0) {
                    $mc = match($product->making_charge_type) {
                        'per_gram'   => $product->making_charge * ($product->weight ?? 0) * $qty,
                        'per_piece'  => $product->making_charge * $qty,
                        'percentage' => ($unitPrice * $qty) * ($product->making_charge / 100),
                        default      => 0,
                    };
                }

                // Wastage
                $wastage = $item['wastage_amount'] ?? 0;
                if ($wastage == 0 && $product->wastage_percent > 0 && $product->weight) {
                    $wastage = $goldRate
                        ? $goldRate->rate_per_gram * ($product->weight * $product->wastage_percent / 100)
                              * (GoldRate::$karatPurity[strtolower($product->karat ?? '24k')] ?? 1)
                              * $qty
                        : 0;
                }

                // Gold value from rate
                $goldV = $item['gold_value'] ?? 0;
                if ($goldV == 0 && $goldRate && $product->weight && $product->karat) {
                    $purity = GoldRate::$karatPurity[strtolower($product->karat)] ?? 1;
                    $goldV  = round($goldRate->rate_per_gram * $product->weight * $purity, 2) * $qty;
                }

                // Gemstone value
                $gemV = $item['gemstone_value'] ?? ($product->gemstone_value * $qty);

                $lineTotal = ($unitPrice * $qty) - $itemDisc;

                $depositCollected = 0;
                if ($product->bottle_deposit_required) {
                    $returned = (bool) ($item['empty_bottle_returned'] ?? false);
                    $depositAmount = (float) ($item['bottle_deposit_amount'] ?? self::DEFAULT_BOTTLE_DEPOSIT);
                    if (!$returned && $depositAmount > 0) {
                        $depositCollected = $depositAmount * $qty;
                        $lineTotal += $depositCollected;
                    }
                }

                $subtotal   += $lineTotal;
                $goldTotal  += $goldV;
                $gemTotal   += $gemV;
                $mcTotal    += $mc;
                $wasteTotal += $wastage;

                $itemData[] = compact('product', 'qty', 'unitPrice', 'itemDisc', 'lineTotal', 'goldV', 'gemV', 'mc', 'wastage', 'depositCollected', 'item');
            }

            $discount = $data['discount'] ?? 0;
            $tax      = $data['tax'] ?? 0;
            $total    = $subtotal - $discount + $tax;
            $payments = $this->normalizePayments($data, $isDraft);
            $amountPaid = $isDraft ? 0 : round((float) $payments->sum('amount'), 2);
            $paymentStatus = $isDraft
                ? 'pending'
                : $this->resolvePaymentStatus($amountPaid, $total, $data['payment_status'] ?? null);
            $paymentMethod = $this->resolvePaymentMethod($payments, $data['payment_method'] ?? 'cash');

            if (!$request->user()->isAdmin() && !empty($data['customer_id'])) {
                $customer = Customer::findOrFail($data['customer_id']);
                if ($customer->branch_id !== $request->user()->branch_id) {
                    throw new \Exception('Selected customer does not belong to your branch.');
                }
            }

            $invPrefix  = 'INV-' . now()->format('Ymd') . '-';
            $lastInv    = Sale::withTrashed()
                ->whereDate('created_at', today())
                ->where('invoice_number', 'like', $invPrefix . '%')
                ->max('invoice_number');
            $invNext    = $lastInv ? ((int) substr($lastInv, -4)) + 1 : 1;
            $invoiceNumber = $invPrefix . str_pad($invNext, 4, '0', STR_PAD_LEFT);

            $sale = Sale::create([
                'branch_id'             => $request->user()->branch_id,
                'invoice_number'        => $invoiceNumber,
                'customer_id'           => $data['customer_id'] ?? null,
                'user_id'               => $request->user()->id,
                'subtotal'              => $subtotal,
                'discount'              => $discount,
                'tax'                   => $tax,
                'tax_rate'              => $data['tax_rate'] ?? 0,
                'total'                 => $total,
                'payment_method'        => $paymentMethod,
                'card_reference'        => ($paymentMethod === 'card') ? ($data['card_reference'] ?? null) : null,
                'payment_status'        => $paymentStatus,
                'amount_paid'           => $amountPaid,
                'status'                => $isDraft ? 'draft' : 'completed',
                'table_number'          => $data['table_number'] ?? null,
                'notes'                 => $data['notes'] ?? null,
                'sold_at'               => !empty($data['sold_at']) ? Carbon::parse($data['sold_at']) : now(),
            ]);

            $this->syncPayments($sale, $payments);

            foreach ($itemData as $i) {
                SaleItem::create([
                    'sale_id'         => $sale->id,
                    'product_id'      => $i['product']->id,
                    'quantity'        => $i['qty'],
                    'unit_price'      => $i['unitPrice'],
                    'discount'        => $i['itemDisc'],
                    'serving_ml'      => (float) ($i['item']['serving_ml'] ?? 0),
                    'open_bottle_id'  => $i['item']['open_bottle_id'] ?? null,
                    'total'           => $i['lineTotal'],
                ]);

                // Only process stock/deposits for completed sales, not drafts
                if (!$isDraft) {
                    $openBottleId = $i['item']['open_bottle_id'] ?? null;
                    $servingMl = (float) ($i['item']['serving_ml'] ?? 0);

                    if ($openBottleId) {
                        $this->handleOpenBottleSell($request, $sale, (int) $openBottleId);
                    } elseif ($servingMl > 0) {
                        $this->handleOpenBottlePour($request, $sale, $i['product'], $servingMl * $i['qty']);
                    } elseif ($i['product']->isStockTracked()) {
                        $i['product']->decrement('stock_quantity', $i['qty']);
                        $i['product']->refresh();
                        StockLedger::record(
                            $i['product'],
                            'OUT',
                            (float) $i['qty'],
                            $request->user()->id,
                            $request->user()->branch_id,
                            'SALE',
                            $sale->id,
                            'Stock reduced from sale',
                            ['invoice_number' => $sale->invoice_number]
                        );
                    }

                    if (($i['depositCollected'] ?? 0) > 0) {
                        BottleDeposit::create([
                            'branch_id' => $request->user()->branch_id,
                            'sale_id' => $sale->id,
                            'customer_id' => $sale->customer_id,
                            'product_id' => $i['product']->id,
                            'user_id' => $request->user()->id,
                            'type' => 'collect',
                            'status' => 'collected',
                            'quantity' => $i['qty'],
                            'amount_per_bottle' => (float) ($i['item']['bottle_deposit_amount'] ?? self::DEFAULT_BOTTLE_DEPOSIT),
                            'total_amount' => $i['depositCollected'],
                            'notes' => 'Auto-collected during sale',
                            'processed_at' => now(),
                        ]);
                    }
                }
            }

            if (!$isDraft) {
                AccountingService::postSale($sale->loadMissing('items.product'));
            }

            AuditLog::record('sale_created', "Sale {$sale->invoice_number} — LKR {$total}", $sale);

            DB::commit();
            return response()->json($sale->load(['items.product', 'customer', 'user', 'payments']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function handleOpenBottlePour(Request $request, Sale $sale, Product $product, float $servingMl): void
    {
        $remaining = $servingMl;

        while ($remaining > 0.001) {
            $openBottle = OpenBottle::where('product_id', $product->id)
                ->where('branch_id', $request->user()->branch_id)
                ->where('status', 'open')
                ->where('remaining_volume_ml', '>', 0)
                ->orderBy('opened_at')
                ->first();

            if (!$openBottle) {
                if ($product->stock_quantity < 1) {
                    throw new \Exception("Insufficient stock for open bottle tracking: {$product->name}");
                }

                $openingVolume = $this->extractMlFromUnit($product->base_unit) ?: 750;
                $product->decrement('stock_quantity', 1);
                $product->refresh();

                $openBottle = OpenBottle::create([
                    'branch_id'          => $request->user()->branch_id,
                    'product_id'         => $product->id,
                    'sale_id'            => $sale->id,
                    'opened_by'          => $request->user()->id,
                    'opening_volume_ml'  => $openingVolume,
                    'remaining_volume_ml'=> $openingVolume,
                    'status'             => 'open',
                    'opened_at'          => now(),
                    'notes'              => 'Auto-opened from sale',
                ]);

                StockLedger::record(
                    $product,
                    'OPEN_BOTTLE',
                    1,
                    $request->user()->id,
                    $request->user()->branch_id,
                    'OPEN_BOTTLE',
                    $openBottle->id,
                    'Bottle opened from sale',
                    ['invoice_number' => $sale->invoice_number]
                );
            }

            $pour = min($remaining, $openBottle->remaining_volume_ml);
            $openBottle->remaining_volume_ml -= $pour;

            if ($openBottle->remaining_volume_ml <= 0) {
                $openBottle->remaining_volume_ml = 0;
                $openBottle->status = 'empty';
                $openBottle->closed_at = now();
            }

            $openBottle->save();
            $remaining -= $pour;
        }
    }

    private function handleOpenBottleSell(Request $request, Sale $sale, int $openBottleId): void
    {
        $openBottle = OpenBottle::where('id', $openBottleId)
            ->where('branch_id', $request->user()->branch_id)
            ->where('status', 'open')
            ->firstOrFail();

        $openBottle->status = 'closed';
        $openBottle->closed_at = now();
        $openBottle->notes = trim(($openBottle->notes ? $openBottle->notes . ' | ' : '') . "Sold via {$sale->invoice_number}");
        $openBottle->save();

        AuditLog::record('open_bottle_sold', "Open bottle #{$openBottle->id} ({$openBottle->remaining_volume_ml}ml) sold via {$sale->invoice_number}", $openBottle);
    }

    private function extractMlFromUnit(?string $value): float
    {
        if (!$value) {
            return 0;
        }

        if (preg_match('/([0-9]+(?:\.[0-9]+)?)\s*ml/i', $value, $matches)) {
            return (float) $matches[1];
        }

        return 0;
    }

    public function show(Sale $sale)
    {
        $this->authorizeBranch($sale->branch_id);
        return response()->json($sale->load(['items.product', 'customer', 'user', 'payments']));
    }

    public function downloadPdf(Sale $sale)
    {
        $this->authorizeBranch($sale->branch_id);
        $sale->load(['items.product', 'customer', 'user', 'payments']);
        $pdf = Pdf::loadView('pdf.invoice', ['sale' => $sale])->setPaper('A4');
        return $pdf->download("Invoice-{$sale->invoice_number}.pdf");
    }

    private function normalizePayments(array $data, bool $isDraft): Collection
    {
        if ($isDraft) {
            return collect();
        }

        if (!empty($data['payments'])) {
            return collect($data['payments'])
                ->map(fn(array $payment) => [
                    'payment_method' => $payment['payment_method'],
                    'amount' => round((float) $payment['amount'], 2),
                    'notes' => $payment['notes'] ?? null,
                ])
                ->filter(fn(array $payment) => $payment['amount'] > 0)
                ->values();
        }

        $amountPaid = round((float) ($data['amount_paid'] ?? 0), 2);
        if ($amountPaid <= 0) {
            return collect();
        }

        return collect([[
            'payment_method' => $data['payment_method'] ?? 'cash',
            'amount' => $amountPaid,
            'notes' => null,
        ]]);
    }

    private function resolvePaymentStatus(float $amountPaid, float $total, ?string $requestedStatus): string
    {
        if ($requestedStatus === 'refunded') {
            return 'refunded';
        }

        if ($amountPaid <= 0) {
            return 'pending';
        }

        if ($amountPaid + 0.009 < $total) {
            return 'partial';
        }

        return 'paid';
    }

    private function resolvePaymentMethod(Collection $payments, string $fallbackMethod): string
    {
        if ($payments->isEmpty()) {
            return $fallbackMethod;
        }

        $methods = $payments->pluck('payment_method')->unique()->values();

        return $methods->count() === 1 ? $methods->first() : 'other';
    }

    private function syncPayments(Sale $sale, Collection $payments): void
    {
        $sale->payments()->delete();

        if ($payments->isEmpty()) {
            return;
        }

        $sale->payments()->createMany(
            $payments->map(fn(array $payment) => [
                'payment_method' => $payment['payment_method'],
                'amount' => $payment['amount'],
                'notes' => $payment['notes'] ?? null,
            ])->all()
        );
    }

    public function update(Request $request, Sale $sale)
    {
        $this->authorizeBranch($sale->branch_id);

        if ($sale->status !== 'draft') {
            return response()->json(['message' => 'Only draft bills can be edited'], 422);
        }

        $data = $request->validate([
            'customer_id'              => 'nullable|exists:customers,id',
            'items'                    => 'required|array|min:1',
            'items.*.product_id'       => 'required|exists:products,id',
            'items.*.quantity'         => 'required|integer|min:1',
            'items.*.unit_price'       => 'required|numeric|min:0',
            'items.*.discount'         => 'nullable|numeric|min:0',
            'items.*.empty_bottle_returned' => 'nullable|boolean',
            'items.*.bottle_deposit_amount' => 'nullable|numeric|min:0',
            'items.*.serving_ml'       => 'nullable|numeric|min:0',
            'items.*.open_bottle_id'   => 'nullable|exists:open_bottles,id',
            'discount'                 => 'nullable|numeric|min:0',
            'tax'                      => 'nullable|numeric|min:0',
            'tax_rate'                 => 'nullable|numeric|min:0|max:100',
            'table_number'             => 'nullable|string|max:50',
            'card_reference'           => 'nullable|string|max:100',
            'notes'                    => 'nullable|string',
            'status'                   => 'nullable|in:draft,completed',
            'payment_method'           => 'nullable|in:cash,card,bank_transfer,cheque,other',
            'payment_status'           => 'nullable|in:pending,paid,partial,refunded',
            'amount_paid'              => 'nullable|numeric|min:0',
            'payments'                 => 'nullable|array|min:1',
            'payments.*.payment_method'=> 'required_with:payments|in:cash,card,bank_transfer,cheque,other',
            'payments.*.amount'        => 'required_with:payments|numeric|min:0.01',
            'payments.*.notes'         => 'nullable|string|max:255',
        ]);

        $isCompleting = ($data['status'] ?? 'draft') === 'completed';

        DB::beginTransaction();
        try {
            $discount = $data['discount'] ?? 0;
            $tax      = $data['tax'] ?? 0;
            $subtotal = 0;

            $itemData = [];
            foreach ($data['items'] as $item) {
                $product = Product::with('category')->findOrFail($item['product_id']);
                $isOpenBottleSell = !empty($item['open_bottle_id']);
                $isPourSale = !empty($item['serving_ml']) && $item['serving_ml'] > 0;
                if ($isCompleting && !$isOpenBottleSell && !$isPourSale && $product->isStockTracked() && $product->stock_quantity < $item['quantity']) {
                    throw new \Exception("Insufficient stock for: {$product->name}");
                }

                $qty       = $item['quantity'];
                $unitPrice = $item['unit_price'];
                $itemDisc  = $item['discount'] ?? 0;
                $lineTotal = ($unitPrice * $qty) - $itemDisc;

                $depositCollected = 0;
                if ($product->bottle_deposit_required) {
                    $returned      = (bool) ($item['empty_bottle_returned'] ?? false);
                    $depositAmount = (float) ($item['bottle_deposit_amount'] ?? self::DEFAULT_BOTTLE_DEPOSIT);
                    if (!$returned && $depositAmount > 0) {
                        $depositCollected = $depositAmount * $qty;
                        $lineTotal       += $depositCollected;
                    }
                }

                $subtotal  += $lineTotal;
                $itemData[] = compact('product', 'qty', 'unitPrice', 'itemDisc', 'lineTotal', 'depositCollected', 'item');
            }

            $total         = $subtotal - $discount + $tax;
            $payments      = $this->normalizePayments($data, !$isCompleting);
            $amountPaid    = $isCompleting ? round((float) $payments->sum('amount'), 2) : 0;
            $paymentStatus = $isCompleting
                ? $this->resolvePaymentStatus($amountPaid, $total, $data['payment_status'] ?? null)
                : 'pending';
            $paymentMethod = $this->resolvePaymentMethod($payments, $data['payment_method'] ?? 'cash');

            if (!$request->user()->isAdmin() && !empty($data['customer_id'])) {
                $customer = Customer::findOrFail($data['customer_id']);
                if ($customer->branch_id !== $request->user()->branch_id) {
                    throw new \Exception('Selected customer does not belong to your branch.');
                }
            }

            $sale->update([
                'customer_id'    => $data['customer_id'] ?? null,
                'subtotal'       => $subtotal,
                'discount'       => $discount,
                'tax'            => $tax,
                'tax_rate'       => $data['tax_rate'] ?? 0,
                'total'          => $total,
                'table_number'   => $data['table_number'] ?? null,
                'card_reference' => ($paymentMethod === 'card') ? ($data['card_reference'] ?? null) : null,
                'notes'          => $data['notes'] ?? null,
                'status'         => $isCompleting ? 'completed' : 'draft',
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'amount_paid'    => $amountPaid,
                'sold_at'        => $isCompleting ? now() : $sale->sold_at,
            ]);

            $this->syncPayments($sale, $payments);

            $sale->items()->delete();
            foreach ($itemData as $i) {
                SaleItem::create([
                    'sale_id'        => $sale->id,
                    'product_id'     => $i['product']->id,
                    'quantity'       => $i['qty'],
                    'unit_price'     => $i['unitPrice'],
                    'discount'       => $i['itemDisc'],
                    'serving_ml'     => (float) ($i['item']['serving_ml'] ?? 0),
                    'open_bottle_id' => $i['item']['open_bottle_id'] ?? null,
                    'total'          => $i['lineTotal'],
                ]);

                if ($isCompleting) {
                    $openBottleId = $i['item']['open_bottle_id'] ?? null;
                    $servingMl = (float) ($i['item']['serving_ml'] ?? 0);

                    if ($openBottleId) {
                        $this->handleOpenBottleSell($request, $sale, (int) $openBottleId);
                    } elseif ($servingMl > 0) {
                        $this->handleOpenBottlePour($request, $sale, $i['product'], $servingMl * $i['qty']);
                    } elseif ($i['product']->isStockTracked()) {
                        $i['product']->decrement('stock_quantity', $i['qty']);
                        $i['product']->refresh();
                        StockLedger::record(
                            $i['product'], 'OUT', (float) $i['qty'],
                            $request->user()->id, $request->user()->branch_id,
                            'SALE', $sale->id, 'Stock reduced from sale',
                            ['invoice_number' => $sale->invoice_number]
                        );
                    }

                    if (($i['depositCollected'] ?? 0) > 0) {
                        BottleDeposit::create([
                            'branch_id'          => $request->user()->branch_id,
                            'sale_id'            => $sale->id,
                            'customer_id'        => $sale->customer_id,
                            'product_id'         => $i['product']->id,
                            'user_id'            => $request->user()->id,
                            'type'               => 'collect',
                            'status'             => 'collected',
                            'quantity'           => $i['qty'],
                            'amount_per_bottle'  => (float) ($i['item']['bottle_deposit_amount'] ?? self::DEFAULT_BOTTLE_DEPOSIT),
                            'total_amount'       => $i['depositCollected'],
                            'notes'              => 'Auto-collected during sale',
                            'processed_at'       => now(),
                        ]);
                    }
                }
            }

            if ($isCompleting) {
                AccountingService::postSale($sale->loadMissing('items.product'));
            }

            AuditLog::record(
                $isCompleting ? 'sale_completed' : 'draft_updated',
                ($isCompleting ? "Draft completed: " : "Draft updated: ") . "{$sale->invoice_number} — LKR {$total}",
                $sale
            );

            DB::commit();
            return response()->json($sale->load(['items.product', 'customer', 'user', 'payments']), 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function destroy(Sale $sale)
    {
        $this->authorizeBranch($sale->branch_id);
        if (request()->user()->role === 'cashier' || !request()->user()->canDeleteTransactions()) {
            abort(403, 'You do not have permission to delete transactions.');
        }
        DB::beginTransaction();
        try {
            foreach ($sale->items as $item) {
                if ($item->product->isStockTracked()) {
                    $item->product->increment('stock_quantity', $item->quantity);
                }
            }
            AuditLog::record('sale_deleted', "Sale {$sale->invoice_number} deleted, stock restored", $sale,
                ['invoice' => $sale->invoice_number, 'total' => $sale->total]);
            $sale->delete();
            DB::commit();
            return response()->json(['message' => 'Sale deleted and stock restored']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function authorizeBranch(?int $branchId): void
    {
        $user = request()->user();
        if (!$user->isAdmin() && (int) $user->branch_id !== (int) $branchId) {
            abort(403, 'Forbidden for this branch.');
        }
    }
}
