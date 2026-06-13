<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\FinishingTask;
use App\Models\JobCard;
use App\Models\JobCosting;
use App\Models\JobConsumable;
use App\Models\PrepressTask;
use App\Models\PressMachine;
use App\Models\ProductionJob;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $branch  = Branch::where('code', 'HQ')->firstOrFail();
        $admin   = User::where('email', 'admin@lmucpress.lk')->firstOrFail();
        $designer = User::where('email', 'designer@lmucpress.lk')->first();
        $operator = User::where('email', 'operator@lmucpress.lk')->first();

        $machines   = PressMachine::where('branch_id', $branch->id)->get()->keyBy('name');
        $customers  = Customer::where('branch_id', $branch->id)->get();

        $heidelberg = $machines['Heidelberg SM 52']   ?? $machines->first();
        $roland     = $machines['Roland 700']          ?? $machines->first();
        $indigo     = $machines['HP Indigo 12000']     ?? $machines->first();
        $polar      = $machines['Polar 115 Guillotine'] ?? $machines->first();

        $dialog    = $customers->firstWhere('name', 'Dialog Axiata PLC');
        $hemas     = $customers->firstWhere('name', 'Hemas Holdings');
        $nestle    = $customers->firstWhere('name', 'Nestlé Lanka Ltd');
        $cfw       = $customers->firstWhere('name', 'Colombo Fashion Week');
        $keells    = $customers->firstWhere('name', 'Keells Retail (Pvt) Ltd');
        $uoc       = $customers->firstWhere('name', 'University of Colombo');
        $saman     = $customers->firstWhere('name', 'Saman Perera');
        $lanka     = $customers->firstWhere('name', 'Lanka Hospitals');

        // fallback so seeder doesn't crash if customers differ
        $c = fn($val) => $val ?? $customers->first();

        // ── Quotations ──────────────────────────────────────────────────

        $quotationDefs = [
            [
                'customer'             => $c($nestle),
                'quotation_number'     => 'QT-20260610-0001',
                'title'                => 'Product Label — Nestlé Milo 500g Tin',
                'product_type'         => 'label',
                'paper_type'           => 'Gloss Coated 200gsm',
                'gsm'                  => 200,
                'size'                 => '220×85mm',
                'width_mm'             => 220,
                'height_mm'            => 85,
                'quantity'             => 50000,
                'color_count'          => 4,
                'printing_method'      => 'offset',
                'plate_cost'           => 8000,
                'paper_cost'           => 42000,
                'ink_cost'             => 18000,
                'finishing_cost'       => 25000,
                'labour_cost'          => 14000,
                'wastage_percent'      => 4,
                'profit_margin_percent'=> 22,
                'tax_rate'             => 8,
                'status'               => 'approved',
                'valid_until'          => now()->addDays(15),
                'notes'                => '4-colour process + Pantone 485 Red spot colour. Gloss lamination. Perforation line required.',
                'terms'                => "50% advance required before job commencement.\n50% on delivery.\nDelivery: 10 working days.",
                'items'                => [
                    ['description' => 'Offset printing 4C+1 Spot, 50,000 labels', 'quantity' => 50000, 'unit_price' => 1.8,  'total' => 90000],
                    ['description' => 'Gloss lamination (single side)',            'quantity' => 50000, 'unit_price' => 0.45, 'total' => 22500],
                    ['description' => 'Die cutting & perforating',                 'quantity' => 50000, 'unit_price' => 0.20, 'total' => 10000],
                    ['description' => 'Packing (rolls of 1000)',                   'quantity' => 50,    'unit_price' => 150,  'total' => 7500],
                ],
            ],
            [
                'customer'             => $c($dialog),
                'quotation_number'     => 'QT-20260611-0001',
                'title'                => 'SIM Card Carrier — Dialog 4G',
                'product_type'         => 'card',
                'paper_type'           => 'Cardboard 300gsm',
                'gsm'                  => 300,
                'size'                 => 'A6',
                'width_mm'             => 148,
                'height_mm'            => 105,
                'quantity'             => 100000,
                'color_count'          => 4,
                'printing_method'      => 'offset',
                'plate_cost'           => 10000,
                'paper_cost'           => 85000,
                'ink_cost'             => 22000,
                'finishing_cost'       => 30000,
                'labour_cost'          => 20000,
                'wastage_percent'      => 3,
                'profit_margin_percent'=> 20,
                'tax_rate'             => 8,
                'status'               => 'sent',
                'valid_until'          => now()->addDays(20),
                'notes'                => 'Pre-punched SIM slots (Standard + Micro + Nano). Scratch-off panel for PIN.',
                'terms'                => "60% advance on order confirmation.\nBalance on collection.\nLeadtime: 12 working days.",
                'items'                => [
                    ['description' => '4C+0 offset print, 300gsm cardboard',        'quantity' => 100000, 'unit_price' => 1.5,  'total' => 150000],
                    ['description' => 'Triple SIM punch (Std/Micro/Nano)',           'quantity' => 100000, 'unit_price' => 0.35, 'total' => 35000],
                    ['description' => 'Scratch-off panel application',               'quantity' => 100000, 'unit_price' => 0.25, 'total' => 25000],
                    ['description' => 'Packing in boxes of 500',                     'quantity' => 200,    'unit_price' => 80,   'total' => 16000],
                ],
            ],
            [
                'customer'             => $c($hemas),
                'quotation_number'     => 'QT-20260612-0001',
                'title'                => 'Pharmaceutical Package Insert — Hemas Baby Lotion',
                'product_type'         => 'leaflet',
                'paper_type'           => 'Bond Paper 80gsm',
                'gsm'                  => 80,
                'size'                 => 'A4 (folded to DL)',
                'width_mm'             => 210,
                'height_mm'            => 297,
                'quantity'             => 30000,
                'color_count'          => 2,
                'printing_method'      => 'offset',
                'plate_cost'           => 4500,
                'paper_cost'           => 18000,
                'ink_cost'             => 5500,
                'finishing_cost'       => 6000,
                'labour_cost'          => 7000,
                'wastage_percent'      => 3,
                'profit_margin_percent'=> 18,
                'tax_rate'             => 8,
                'status'               => 'draft',
                'valid_until'          => now()->addDays(30),
                'notes'                => '2-colour (black + Pantone 299 Blue). Folded to 1/3 A4 (6 panels). Must meet pharmaceutical print standards.',
                'items'                => [
                    ['description' => '2C offset printing, 80gsm bond',       'quantity' => 30000, 'unit_price' => 1.2,  'total' => 36000],
                    ['description' => 'Roll fold (6 panels)',                  'quantity' => 30000, 'unit_price' => 0.25, 'total' => 7500],
                    ['description' => 'Quality inspection & certification',    'quantity' => 1,     'unit_price' => 5000, 'total' => 5000],
                ],
            ],
            [
                'customer'             => $c($keells),
                'quotation_number'     => 'QT-20260612-0002',
                'title'                => 'Shopping Bags — Keells Super (2 sizes)',
                'product_type'         => 'bag',
                'paper_type'           => 'Kraft Paper 120gsm',
                'gsm'                  => 120,
                'size'                 => 'Large + Medium',
                'width_mm'             => 380,
                'height_mm'            => 450,
                'quantity'             => 20000,
                'color_count'          => 3,
                'printing_method'      => 'offset',
                'plate_cost'           => 6000,
                'paper_cost'           => 55000,
                'ink_cost'             => 12000,
                'finishing_cost'       => 40000,
                'labour_cost'          => 25000,
                'wastage_percent'      => 5,
                'profit_margin_percent'=> 25,
                'tax_rate'             => 8,
                'status'               => 'converted',
                'valid_until'          => now()->subDays(5),
                'notes'                => '3-colour (brand colours). 10,000 large (38×45cm) + 10,000 medium (30×35cm). Twisted rope handles. Gloss varnish.',
                'items'                => [
                    ['description' => 'Large shopping bag print + assembly (10,000)',  'quantity' => 10000, 'unit_price' => 18,  'total' => 180000],
                    ['description' => 'Medium shopping bag print + assembly (10,000)', 'quantity' => 10000, 'unit_price' => 12,  'total' => 120000],
                    ['description' => 'Gloss spot varnish',                            'quantity' => 20000, 'unit_price' => 0.8, 'total' => 16000],
                ],
            ],
            [
                'customer'             => $c($cfw),
                'quotation_number'     => 'QT-20260613-0001',
                'title'                => 'Event Programme — CFW 2026 Runway Show',
                'product_type'         => 'booklet',
                'paper_type'           => 'Art Paper 170gsm',
                'gsm'                  => 170,
                'size'                 => 'A5',
                'width_mm'             => 148,
                'height_mm'            => 210,
                'quantity'             => 800,
                'color_count'          => 4,
                'printing_method'      => 'digital',
                'plate_cost'           => 0,
                'paper_cost'           => 12000,
                'ink_cost'             => 8000,
                'finishing_cost'       => 18000,
                'labour_cost'          => 8000,
                'wastage_percent'      => 3,
                'profit_margin_percent'=> 30,
                'tax_rate'             => 8,
                'status'               => 'sent',
                'valid_until'          => now()->addDays(5),
                'notes'                => '32-page A5 booklet, saddle-stitched. Soft-touch matte lamination on cover. Gold foil on title. 170gsm interior + 300gsm cover.',
                'items'                => [
                    ['description' => 'Digital print — 32pp A5 booklet interior',   'quantity' => 800, 'unit_price' => 35,  'total' => 28000],
                    ['description' => 'Cover print 300gsm + matte lamination',       'quantity' => 800, 'unit_price' => 22,  'total' => 17600],
                    ['description' => 'Gold foil stamping on title',                 'quantity' => 800, 'unit_price' => 15,  'total' => 12000],
                    ['description' => 'Saddle-stitch binding',                       'quantity' => 800, 'unit_price' => 8,   'total' => 6400],
                ],
            ],
        ];

        foreach ($quotationDefs as $def) {
            if (Quotation::where('quotation_number', $def['quotation_number'])->exists()) {
                continue;
            }
            $customerObj = $def['customer'];
            $items       = $def['items'];
            unset($def['customer'], $def['items']);

            $qt = Quotation::create([
                ...$def,
                'branch_id'   => $branch->id,
                'customer_id' => $customerObj->id,
                'created_by'  => $admin->id,
            ]);
            $qt->calculateTotals();
            $qt->save();

            foreach ($items as $i => $item) {
                QuotationItem::create([
                    'quotation_id' => $qt->id,
                    'description'  => $item['description'],
                    'quantity'     => $item['quantity'],
                    'unit_price'   => $item['unit_price'],
                    'total'        => $item['total'],
                    'sort_order'   => $i,
                ]);
            }
        }

        // ── Job Cards with full lifecycle data ──────────────────────────

        $jobDefs = [
            // 1 — DELIVERED, full history
            [
                'customer'               => $c($keells),
                'job_number'             => 'JC-20260601-0001',
                'title'                  => 'Shopping Bags — Keells Super',
                'product_description'    => '38×45cm kraft shopping bag, 3C, gloss varnish, rope handle',
                'paper_type'             => 'Kraft Paper 120gsm',
                'gsm'                    => 120,
                'size'                   => '38×45cm',
                'quantity_ordered'       => 20000,
                'color_count'            => '3+0',
                'printing_method'        => 'offset',
                'printing_instructions'  => 'Match Keells brand Pantone 207 Red and Pantone 280 Blue. Apply gloss spot varnish on logo area.',
                'finishing_instructions' => 'Gloss varnish, twisted rope handle attachment, pack in boxes of 200.',
                'status'                 => 'delivered',
                'artwork_status'         => 'approved',
                'is_priority'            => false,
                'order_date'             => now()->subDays(18),
                'scheduled_date'         => now()->subDays(15),
                'due_date'               => now()->subDays(8),
                'completed_at'           => now()->subDays(9),
                'machine'                => $heidelberg,
                'prepress'               => ['status' => 'completed', 'plate_status' => 'completed', 'plate_count' => 3, 'revision_count' => 1, 'proof_sent_at' => now()->subDays(16), 'proof_approved_at' => now()->subDays(15)],
                'finishing'              => ['cutting' => true, 'folding' => false, 'binding' => false, 'lamination' => false, 'uv_coating' => true, 'foiling' => false, 'die_cutting' => true, 'packaging' => true, 'status' => 'completed'],
                'costing'                => ['paper_sheets' => 22000, 'paper_rate_per_sheet' => 3.8, 'ink_colours' => 3, 'ink_cost_per_colour' => 9500, 'plate_count' => 3, 'plate_cost_each' => 420, 'machine_hours' => 6.5, 'machine_rate_per_hour' => 4500, 'labour_hours' => 18, 'labour_rate_per_hour' => 650, 'electricity_cost' => 3200, 'outsource_cost' => 12000, 'outsource_description' => 'Rope handle fabrication outsourced', 'waste_percentage' => 5],
                'revenue'                => 316000,
                'production_runs'        => [
                    ['output_quantity' => 12000, 'waste_quantity' => 400, 'status' => 'completed', 'notes' => 'Morning run — excellent registration'],
                    ['output_quantity' => 8200,  'waste_quantity' => 200, 'status' => 'completed', 'notes' => 'Afternoon run — completed order'],
                ],
                'consumables'            => [
                    ['type' => 'paper',  'description' => 'Kraft Paper 120gsm',        'quantity' => 220,  'unit' => 'sheets (100s)', 'unit_cost' => 850],
                    ['type' => 'ink',    'description' => 'Pantone 207 Red Ink',        'quantity' => 3.2,  'unit' => 'kg',            'unit_cost' => 2800],
                    ['type' => 'ink',    'description' => 'Pantone 280 Blue Ink',       'quantity' => 2.8,  'unit' => 'kg',            'unit_cost' => 2800],
                    ['type' => 'plate',  'description' => 'CTP Plate 700×1000mm',       'quantity' => 3,    'unit' => 'pcs',           'unit_cost' => 420],
                    ['type' => 'other',  'description' => 'Twisted rope handle (pairs)', 'quantity' => 20200,'unit' => 'pcs',           'unit_cost' => 0.55],
                ],
            ],
            // 2 — READY for dispatch
            [
                'customer'               => $c($dialog),
                'job_number'             => 'JC-20260605-0001',
                'title'                  => 'SIM Card Carrier — Dialog 4G',
                'product_description'    => 'A6 300gsm cardboard SIM carrier, 4C, triple SIM punch, scratch panel',
                'paper_type'             => 'Cardboard 300gsm',
                'gsm'                    => 300,
                'size'                   => 'A6',
                'quantity_ordered'       => 100000,
                'color_count'            => '4+0',
                'printing_method'        => 'offset',
                'printing_instructions'  => 'Match Dialog corporate colours. Triple SIM punch critical — check registration on first 50 sheets.',
                'finishing_instructions' => 'Lamination, triple SIM punch, scratch-off panel. Bundle in packs of 500.',
                'status'                 => 'ready',
                'artwork_status'         => 'approved',
                'is_priority'            => true,
                'order_date'             => now()->subDays(10),
                'scheduled_date'         => now()->subDays(8),
                'due_date'               => now()->addDays(1),
                'completed_at'           => null,
                'machine'                => $roland,
                'prepress'               => ['status' => 'completed', 'plate_status' => 'completed', 'plate_count' => 4, 'revision_count' => 0, 'proof_sent_at' => now()->subDays(9), 'proof_approved_at' => now()->subDays(8)],
                'finishing'              => ['cutting' => true, 'folding' => false, 'binding' => false, 'lamination' => true, 'uv_coating' => false, 'foiling' => false, 'die_cutting' => true, 'packaging' => true, 'lamination_type' => 'gloss', 'status' => 'completed'],
                'costing'                => ['paper_sheets' => 105000, 'paper_rate_per_sheet' => 1.2, 'ink_colours' => 4, 'ink_cost_per_colour' => 12000, 'plate_count' => 4, 'plate_cost_each' => 380, 'machine_hours' => 12, 'machine_rate_per_hour' => 5500, 'labour_hours' => 32, 'labour_rate_per_hour' => 650, 'electricity_cost' => 8500, 'outsource_cost' => 15000, 'outsource_description' => 'Scratch-off panel printing (outsourced to speciality finisher)', 'waste_percentage' => 3],
                'revenue'                => 226000,
                'production_runs'        => [
                    ['output_quantity' => 50000, 'waste_quantity' => 1200, 'status' => 'completed', 'notes' => 'Day 1 run on Roland 700 — high speed'],
                    ['output_quantity' => 51200, 'waste_quantity' => 800,  'status' => 'completed', 'notes' => 'Day 2 run — job completed'],
                ],
                'consumables'            => [
                    ['type' => 'paper', 'description' => 'Cardboard 300gsm',      'quantity' => 1050,  'unit' => 'sheets (100s)', 'unit_cost' => 120],
                    ['type' => 'ink',   'description' => 'CMYK Offset Ink Set',   'quantity' => 14.5,  'unit' => 'kg',            'unit_cost' => 2950],
                    ['type' => 'plate', 'description' => 'CTP Plate 400×500mm',   'quantity' => 4,     'unit' => 'pcs',           'unit_cost' => 380],
                    ['type' => 'other', 'description' => 'Gloss lamination film', 'quantity' => 105,   'unit' => 'm²',            'unit_cost' => 85],
                ],
            ],
            // 3 — QUALITY CHECK
            [
                'customer'               => $c($nestle),
                'job_number'             => 'JC-20260608-0001',
                'title'                  => 'Product Label — Nestlé Milo 500g',
                'product_description'    => '220×85mm gloss label, 4C+1 spot, die cut, perforation',
                'paper_type'             => 'Gloss Coated 200gsm',
                'gsm'                    => 200,
                'size'                   => '220×85mm',
                'quantity_ordered'       => 50000,
                'color_count'            => '4+1',
                'printing_method'        => 'offset',
                'printing_instructions'  => '4C process + Pantone 485 Red. Ensure colour consistency across entire run (dE < 2). Ink density targets: C=1.45 M=1.50 Y=1.40 K=1.75.',
                'finishing_instructions' => 'Gloss lamination, die cut to shape, perforation at 25mm from bottom edge.',
                'status'                 => 'quality_check',
                'artwork_status'         => 'approved',
                'is_priority'            => true,
                'order_date'             => now()->subDays(8),
                'scheduled_date'         => now()->subDays(6),
                'due_date'               => now()->addDays(2),
                'completed_at'           => null,
                'machine'                => $heidelberg,
                'prepress'               => ['status' => 'completed', 'plate_status' => 'completed', 'plate_count' => 5, 'revision_count' => 2, 'revision_notes' => 'Pantone spot colour adjusted twice to match brand guide', 'proof_sent_at' => now()->subDays(7), 'proof_approved_at' => now()->subDays(6)],
                'finishing'              => ['cutting' => false, 'folding' => false, 'binding' => false, 'lamination' => true, 'uv_coating' => false, 'foiling' => false, 'die_cutting' => true, 'packaging' => true, 'lamination_type' => 'gloss', 'status' => 'completed'],
                'costing'                => ['paper_sheets' => 53000, 'paper_rate_per_sheet' => 1.05, 'ink_colours' => 5, 'ink_cost_per_colour' => 3800, 'plate_count' => 5, 'plate_cost_each' => 420, 'machine_hours' => 8.5, 'machine_rate_per_hour' => 4500, 'labour_hours' => 24, 'labour_rate_per_hour' => 650, 'electricity_cost' => 5200, 'outsource_cost' => 0, 'waste_percentage' => 4],
                'revenue'                => 130000,
                'production_runs'        => [
                    ['output_quantity' => 30000, 'waste_quantity' => 1500, 'status' => 'completed', 'notes' => 'Colour calibration run — first 500 sheets rejected, adjusted ink keys'],
                    ['output_quantity' => 21000, 'waste_quantity' => 500,  'status' => 'completed', 'notes' => 'Balance run — consistent colour throughout'],
                ],
                'consumables'            => [
                    ['type' => 'paper', 'description' => 'Glossy Coated 200gsm',    'quantity' => 530,  'unit' => 'sheets (100s)', 'unit_cost' => 105],
                    ['type' => 'ink',   'description' => 'CMYK Offset Ink Set',      'quantity' => 8.2,  'unit' => 'kg',            'unit_cost' => 2950],
                    ['type' => 'ink',   'description' => 'Pantone 485 Red Ink',      'quantity' => 2.5,  'unit' => 'kg',            'unit_cost' => 3400],
                    ['type' => 'plate', 'description' => 'CTP Plate 700×1000mm',     'quantity' => 5,    'unit' => 'pcs',           'unit_cost' => 420],
                ],
            ],
            // 4 — PRINTING (in progress)
            [
                'customer'               => $c($cfw),
                'job_number'             => 'JC-20260611-0001',
                'title'                  => 'Event Programme — CFW 2026',
                'product_description'    => '32pp A5 saddle-stitched booklet, matte lamination + gold foil cover',
                'paper_type'             => 'Art Paper 170gsm',
                'gsm'                    => 170,
                'size'                   => 'A5',
                'quantity_ordered'       => 800,
                'color_count'            => '4+4',
                'printing_method'        => 'digital',
                'printing_instructions'  => 'Interior: 4C both sides. Cover: 4C + gold foil on title. Matte lamination on outer cover.',
                'finishing_instructions' => 'Matte lamination (cover only), gold foil stamp on title text, saddle-stitch binding.',
                'status'                 => 'printing',
                'artwork_status'         => 'approved',
                'is_priority'            => true,
                'order_date'             => now()->subDays(4),
                'scheduled_date'         => now()->subDays(3),
                'due_date'               => now()->addDays(2),
                'completed_at'           => null,
                'machine'                => $indigo,
                'prepress'               => ['status' => 'completed', 'plate_status' => 'completed', 'plate_count' => 0, 'revision_count' => 1, 'proof_sent_at' => now()->subDays(3), 'proof_approved_at' => now()->subDays(2)],
                'finishing'              => ['cutting' => false, 'folding' => false, 'binding' => true, 'lamination' => true, 'uv_coating' => false, 'foiling' => true, 'die_cutting' => false, 'packaging' => true, 'lamination_type' => 'matte', 'binding_type' => 'saddle_stitch', 'status' => 'pending'],
                'costing'                => ['paper_sheets' => 850, 'paper_rate_per_sheet' => 18, 'ink_colours' => 4, 'ink_cost_per_colour' => 1800, 'plate_count' => 0, 'plate_cost_each' => 0, 'machine_hours' => 3.5, 'machine_rate_per_hour' => 3500, 'labour_hours' => 8, 'labour_rate_per_hour' => 650, 'electricity_cost' => 1200, 'outsource_cost' => 5000, 'outsource_description' => 'Gold foil stamping', 'waste_percentage' => 3],
                'revenue'                => 64000,
                'production_runs'        => [
                    ['output_quantity' => 400, 'waste_quantity' => 20, 'status' => 'completed', 'notes' => 'Interior sheets — batch 1 done'],
                ],
                'consumables'            => [
                    ['type' => 'paper', 'description' => 'Art Paper 170gsm A4',     'quantity' => 850,  'unit' => 'sheets', 'unit_cost' => 18],
                    ['type' => 'paper', 'description' => 'Cardboard 300gsm (cover)', 'quantity' => 90,  'unit' => 'sheets', 'unit_cost' => 110],
                    ['type' => 'other', 'description' => 'Matte lamination film',    'quantity' => 2.5,  'unit' => 'm²',     'unit_cost' => 90],
                ],
            ],
            // 5 — PLATE MAKING
            [
                'customer'               => $c($lanka),
                'job_number'             => 'JC-20260612-0001',
                'title'                  => 'Annual Report 2025 — Lanka Hospitals',
                'product_description'    => '48pp A4 saddle-stitched report, 170gsm interior, 300gsm cover',
                'paper_type'             => 'Art Paper 170gsm',
                'gsm'                    => 170,
                'size'                   => 'A4',
                'quantity_ordered'       => 300,
                'color_count'            => '4+4',
                'printing_method'        => 'offset',
                'printing_instructions'  => 'Full bleed on all pages. Bleed 3mm. Images must be 300dpi minimum.',
                'finishing_instructions' => 'Soft-touch matte lamination (cover), saddle-stitch binding.',
                'status'                 => 'plate_making',
                'artwork_status'         => 'approved',
                'is_priority'            => false,
                'order_date'             => now()->subDays(3),
                'scheduled_date'         => now()->addDays(1),
                'due_date'               => now()->addDays(6),
                'completed_at'           => null,
                'machine'                => $heidelberg,
                'prepress'               => ['status' => 'in_progress', 'plate_status' => 'in_progress', 'plate_count' => 12, 'revision_count' => 0, 'proof_sent_at' => now()->subDays(2), 'proof_approved_at' => now()->subDays(1)],
                'finishing'              => ['cutting' => false, 'folding' => false, 'binding' => true, 'lamination' => true, 'uv_coating' => false, 'foiling' => false, 'die_cutting' => false, 'packaging' => true, 'lamination_type' => 'soft_touch_matte', 'binding_type' => 'saddle_stitch', 'status' => 'pending'],
                'costing'                => null,
                'revenue'                => 95000,
                'production_runs'        => [],
                'consumables'            => [],
            ],
            // 6 — PROOF APPROVAL
            [
                'customer'               => $c($uoc),
                'job_number'             => 'JC-20260613-0001',
                'title'                  => 'Exam Answer Books — UoC June 2026',
                'product_description'    => 'A4 exam booklet, 20pp, 80gsm bond, 1C both sides, staple bound',
                'paper_type'             => 'Bond Paper 80gsm',
                'gsm'                    => 80,
                'size'                   => 'A4',
                'quantity_ordered'       => 5000,
                'color_count'            => '1+1',
                'printing_method'        => 'offset',
                'printing_instructions'  => 'Black only. Registration marks must be within 0.1mm. UoC crest must be clear and well-inked.',
                'finishing_instructions' => 'Folded and collated, 2-staple saddle stitch.',
                'status'                 => 'proof_approval',
                'artwork_status'         => 'approved',
                'is_priority'            => false,
                'order_date'             => now()->subDays(2),
                'scheduled_date'         => now()->addDays(3),
                'due_date'               => now()->addDays(10),
                'completed_at'           => null,
                'machine'                => $heidelberg,
                'prepress'               => ['status' => 'in_progress', 'plate_status' => 'pending', 'plate_count' => 2, 'revision_count' => 0, 'proof_sent_at' => now()->subDays(1), 'proof_approved_at' => null],
                'finishing'              => ['cutting' => false, 'folding' => true, 'binding' => true, 'lamination' => false, 'uv_coating' => false, 'foiling' => false, 'die_cutting' => false, 'packaging' => true, 'binding_type' => 'saddle_stitch', 'status' => 'pending'],
                'costing'                => null,
                'revenue'                => 45000,
                'production_runs'        => [],
                'consumables'            => [],
            ],
            // 7 — DESIGNING
            [
                'customer'               => $c($saman),
                'job_number'             => 'JC-20260614-0001',
                'title'                  => 'Wedding Invitation — Perera / Fernando',
                'product_description'    => 'A5 folded card, 300gsm ivory, 4C + gold foil + embossing, envelope included',
                'paper_type'             => 'Cardboard 300gsm',
                'gsm'                    => 300,
                'size'                   => 'A5 folded',
                'quantity_ordered'       => 250,
                'color_count'            => '4+0',
                'printing_method'        => 'digital',
                'printing_instructions'  => 'Ivory/cream tint background. Gold foil on names and floral motif. Emboss on venue details.',
                'finishing_instructions' => 'Gold foil stamp, blind embossing, fold, insert into matching envelope.',
                'status'                 => 'designing',
                'artwork_status'         => 'received',
                'is_priority'            => false,
                'order_date'             => now()->subDays(1),
                'scheduled_date'         => now()->addDays(5),
                'due_date'               => now()->addDays(12),
                'completed_at'           => null,
                'machine'                => $indigo,
                'prepress'               => ['status' => 'in_progress', 'plate_status' => 'pending', 'plate_count' => 0, 'revision_count' => 0, 'proof_sent_at' => null, 'proof_approved_at' => null],
                'finishing'              => ['cutting' => false, 'folding' => true, 'binding' => false, 'lamination' => false, 'uv_coating' => false, 'foiling' => true, 'die_cutting' => false, 'packaging' => true, 'status' => 'pending'],
                'costing'                => null,
                'revenue'                => 28000,
                'production_runs'        => [],
                'consumables'            => [],
            ],
            // 8 — WAITING / new order
            [
                'customer'               => $c($hemas),
                'job_number'             => 'JC-20260614-0002',
                'title'                  => 'Package Insert — Hemas Baby Lotion',
                'product_description'    => 'DL leaflet (1/3 A4), 80gsm bond, 2C, 6-panel roll fold',
                'paper_type'             => 'Bond Paper 80gsm',
                'gsm'                    => 80,
                'size'                   => 'DL (1/3 A4)',
                'quantity_ordered'       => 30000,
                'color_count'            => '2+2',
                'printing_method'        => 'offset',
                'printing_instructions'  => 'Black + Pantone 299 Blue. Text must be legible at 6pt (pharmaceutical requirement).',
                'finishing_instructions' => '6-panel roll fold.',
                'status'                 => 'waiting',
                'artwork_status'         => 'pending',
                'is_priority'            => false,
                'order_date'             => now()->toDateString(),
                'scheduled_date'         => now()->addDays(7),
                'due_date'               => now()->addDays(14),
                'completed_at'           => null,
                'machine'                => $heidelberg,
                'prepress'               => ['status' => 'pending', 'plate_status' => 'pending', 'plate_count' => 2, 'revision_count' => 0, 'proof_sent_at' => null, 'proof_approved_at' => null],
                'finishing'              => ['cutting' => false, 'folding' => true, 'binding' => false, 'lamination' => false, 'uv_coating' => false, 'foiling' => false, 'die_cutting' => false, 'packaging' => true, 'status' => 'pending'],
                'costing'                => null,
                'revenue'                => 48500,
                'production_runs'        => [],
                'consumables'            => [],
            ],
        ];

        foreach ($jobDefs as $def) {
            if (JobCard::where('job_number', $def['job_number'])->exists()) {
                continue;
            }

            $prepress       = $def['prepress'];
            $finishing      = $def['finishing'];
            $costingDef     = $def['costing'];
            $revenue        = $def['revenue'];
            $productionRuns = $def['production_runs'];
            $consumables    = $def['consumables'];
            $machine        = $def['machine'];
            $customerObj    = $def['customer'];

            unset($def['prepress'], $def['finishing'], $def['costing'], $def['revenue'],
                  $def['production_runs'], $def['consumables'], $def['machine'], $def['customer']);

            $job = JobCard::create([
                ...$def,
                'branch_id'            => $branch->id,
                'customer_id'          => $customerObj->id,
                'machine_id'           => $machine?->id,
                'assigned_operator_id' => $operator?->id,
                'qr_code'              => strtoupper(substr(md5($def['job_number']), 0, 10)),
                'created_by'           => $admin->id,
            ]);

            // Prepress task
            PrepressTask::create([
                'branch_id'          => $branch->id,
                'job_card_id'        => $job->id,
                'status'             => $prepress['status'],
                'plate_status'       => $prepress['plate_status'],
                'plate_count'        => $prepress['plate_count'],
                'revision_count'     => $prepress['revision_count'],
                'revision_notes'     => $prepress['revision_notes'] ?? null,
                'proof_sent_at'      => $prepress['proof_sent_at'] ?? null,
                'proof_approved_at'  => $prepress['proof_approved_at'] ?? null,
                'proof_approved_by'  => $prepress['proof_approved_at'] ? $admin->id : null,
                'assigned_to'        => $designer?->id,
            ]);

            // Finishing task
            FinishingTask::create([
                'branch_id'   => $branch->id,
                'job_card_id' => $job->id,
                ...$finishing,
            ]);

            // Production runs
            foreach ($productionRuns as $run) {
                ProductionJob::create([
                    'branch_id'       => $branch->id,
                    'job_card_id'     => $job->id,
                    'machine_id'      => $machine?->id,
                    'operator_id'     => $operator?->id,
                    'start_time'      => now()->subDays(2)->setTime(8, 0),
                    'end_time'        => now()->subDays(2)->setTime(14, 0),
                    'output_quantity' => $run['output_quantity'],
                    'waste_quantity'  => $run['waste_quantity'],
                    'status'          => $run['status'],
                    'notes'           => $run['notes'],
                ]);
            }

            // Consumables
            foreach ($consumables as $con) {
                JobConsumable::create([
                    'branch_id'   => $branch->id,
                    'job_card_id' => $job->id,
                    'type'        => $con['type'],
                    'description' => $con['description'],
                    'quantity'    => $con['quantity'],
                    'unit'        => $con['unit'],
                    'unit_cost'   => $con['unit_cost'],
                    'total_cost'  => $con['quantity'] * $con['unit_cost'],
                ]);
            }

            // Job costing
            if ($costingDef) {
                $d = $costingDef;
                $paperCost   = $d['paper_sheets'] * $d['paper_rate_per_sheet'];
                $inkCost     = $d['ink_colours'] * $d['ink_cost_per_colour'];
                $plateCost   = $d['plate_count'] * $d['plate_cost_each'];
                $machineCost = $d['machine_hours'] * $d['machine_rate_per_hour'];
                $labourCost  = $d['labour_hours'] * $d['labour_rate_per_hour'];
                $elec        = $d['electricity_cost'];
                $outsource   = $d['outsource_cost'];
                $materialsSubtotal = $paperCost + $inkCost + $plateCost + $machineCost + $labourCost + $elec + $outsource;
                $wasteCost   = round($materialsSubtotal * ($d['waste_percentage'] / 100), 2);
                $totalActual = round($materialsSubtotal + $wasteCost, 2);
                $profit      = round($revenue - $totalActual, 2);
                $margin      = $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0;

                JobCosting::create([
                    'job_card_id'           => $job->id,
                    'branch_id'             => $branch->id,
                    'paper_sheets'          => $d['paper_sheets'],
                    'paper_rate_per_sheet'  => $d['paper_rate_per_sheet'],
                    'paper_cost'            => round($paperCost, 2),
                    'ink_colours'           => $d['ink_colours'],
                    'ink_cost_per_colour'   => $d['ink_cost_per_colour'],
                    'ink_cost'              => round($inkCost, 2),
                    'plate_count'           => $d['plate_count'],
                    'plate_cost_each'       => $d['plate_cost_each'],
                    'plate_cost'            => round($plateCost, 2),
                    'machine_hours'         => $d['machine_hours'],
                    'machine_rate_per_hour' => $d['machine_rate_per_hour'],
                    'machine_cost'          => round($machineCost, 2),
                    'labour_hours'          => $d['labour_hours'],
                    'labour_rate_per_hour'  => $d['labour_rate_per_hour'],
                    'labour_cost'           => round($labourCost, 2),
                    'electricity_cost'      => $elec,
                    'outsource_cost'        => $outsource,
                    'outsource_description' => $d['outsource_description'] ?? null,
                    'waste_percentage'      => $d['waste_percentage'],
                    'waste_cost'            => $wasteCost,
                    'total_actual_cost'     => $totalActual,
                    'revenue'               => $revenue,
                    'profit'                => $profit,
                    'profit_margin'         => $margin,
                ]);
            }
        }

        $this->command->info('Demo data seeded: 5 quotations + 8 job cards (full lifecycle).');
    }
}
