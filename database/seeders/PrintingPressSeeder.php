<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Customer;
use App\Models\JobCard;
use App\Models\PressMachine;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Support\StockLedger;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PrintingPressSeeder extends Seeder
{
    public function run(): void
    {
        // ── Branch ──────────────────────────────────────────────────
        $branch = Branch::firstOrCreate(
            ['code' => 'HQ'],
            [
                'name'    => 'LMUC Press — Head Office',
                'address' => '45 Industrial Zone, Colombo 10',
                'city'    => 'Colombo',
                'country' => 'Sri Lanka',
            ]
        );

        // ── Users ───────────────────────────────────────────────────
        $admin = User::firstOrCreate(
            ['email' => 'admin@lmucpress.lk'],
            [
                'name'      => 'Admin',
                'password'  => Hash::make('password'),
                'role'      => 'admin',
                'branch_id' => $branch->id,
                'is_active' => true,
            ]
        );

        $users = [
            ['name' => 'Kamal Silva',    'email' => 'sales@lmucpress.lk',      'role' => 'sales'],
            ['name' => 'Priya Fernando', 'email' => 'estimator@lmucpress.lk',  'role' => 'estimator'],
            ['name' => 'Asanka Perera',  'email' => 'designer@lmucpress.lk',   'role' => 'designer'],
            ['name' => 'Nimal Jayasinghe','email' => 'prodmgr@lmucpress.lk',   'role' => 'production_manager'],
            ['name' => 'Chaminda Ratna', 'email' => 'operator@lmucpress.lk',   'role' => 'machine_operator'],
            ['name' => 'Thilini Perera', 'email' => 'store@lmucpress.lk',      'role' => 'store_keeper'],
            ['name' => 'Roshan Bandara', 'email' => 'accounts@lmucpress.lk',   'role' => 'accountant'],
            ['name' => 'Dilshan Madara', 'email' => 'dispatch@lmucpress.lk',   'role' => 'dispatch_officer'],
        ];

        foreach ($users as $u) {
            User::firstOrCreate(
                ['email' => $u['email']],
                [
                    'name'      => $u['name'],
                    'password'  => Hash::make('password'),
                    'role'      => $u['role'],
                    'branch_id' => $branch->id,
                    'is_active' => true,
                ]
            );
        }

        // ── Material Categories ──────────────────────────────────────
        $categories = [
            ['name' => 'Paper & Board'],
            ['name' => 'Inks & Chemicals'],
            ['name' => 'Plates & Films'],
            ['name' => 'Packaging Materials'],
            ['name' => 'Printing Services'],
            ['name' => 'Finishing Services'],
            ['name' => 'Consumables'],
        ];

        $catMap = [];
        foreach ($categories as $cat) {
            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $cat['name']));
            $catMap[$cat['name']] = Category::firstOrCreate(
                ['slug' => $slug, 'branch_id' => $branch->id],
                ['name' => $cat['name'], 'is_active' => true]
            );
        }

        // ── Press Machines ───────────────────────────────────────────
        $machines = [
            ['name' => 'Heidelberg SM 52',     'machine_type' => 'printing',   'manufacturer' => 'Heidelberg',  'capacity_per_hour' => 8000,  'model_number' => 'SM52-4'],
            ['name' => 'Roland 700',           'machine_type' => 'printing',   'manufacturer' => 'MAN Roland', 'capacity_per_hour' => 15000, 'model_number' => 'R700'],
            ['name' => 'HP Indigo 12000',      'machine_type' => 'printing',   'manufacturer' => 'HP',          'capacity_per_hour' => 2000,  'model_number' => 'Indigo12000'],
            ['name' => 'Polar 115 Guillotine', 'machine_type' => 'cutting',    'manufacturer' => 'Polar',       'capacity_per_hour' => 500,   'model_number' => 'N115'],
            ['name' => 'Heidelberg Stahlfolder','machine_type' => 'folding',   'manufacturer' => 'Heidelberg',  'capacity_per_hour' => 3000,  'model_number' => 'TH82'],
            ['name' => 'Autobond Laminator',   'machine_type' => 'lamination', 'manufacturer' => 'Autobond',    'capacity_per_hour' => 1500,  'model_number' => 'Mini76TC'],
            ['name' => 'Muller Martini Binder','machine_type' => 'binding',    'manufacturer' => 'Muller Martini', 'capacity_per_hour' => 2000, 'model_number' => 'Prima'],
        ];

        $machineObjs = [];
        foreach ($machines as $m) {
            $machineObjs[] = PressMachine::firstOrCreate(
                ['name' => $m['name'], 'branch_id' => $branch->id],
                [...$m, 'branch_id' => $branch->id, 'status' => 'active']
            );
        }

        // ── Raw Materials / Products ─────────────────────────────────
        $materials = [
            // Paper & Board
            ['name' => 'Art Paper 128gsm A3',     'sku' => 'PAP-128-A3', 'category' => 'Paper & Board',    'purchase_price' => 45,   'selling_price' => 60,   'stock_quantity' => 5000, 'reorder_level' => 500, 'material_type' => 'paper',  'gsm' => '128', 'paper_size' => 'A3', 'bundle_size' => 100],
            ['name' => 'Art Paper 170gsm A4',     'sku' => 'PAP-170-A4', 'category' => 'Paper & Board',    'purchase_price' => 55,   'selling_price' => 75,   'stock_quantity' => 3000, 'reorder_level' => 300, 'material_type' => 'paper',  'gsm' => '170', 'paper_size' => 'A4', 'bundle_size' => 100],
            ['name' => 'Bond Paper 80gsm',        'sku' => 'BON-080',    'category' => 'Paper & Board',    'purchase_price' => 28,   'selling_price' => 40,   'stock_quantity' => 8000, 'reorder_level' => 800, 'material_type' => 'paper',  'gsm' => '80',  'paper_size' => 'A4', 'bundle_size' => 500],
            ['name' => 'Cardboard 300gsm',        'sku' => 'CAR-300',    'category' => 'Paper & Board',    'purchase_price' => 95,   'selling_price' => 130,  'stock_quantity' => 2000, 'reorder_level' => 200, 'material_type' => 'paper',  'gsm' => '300', 'paper_size' => 'A3', 'bundle_size' => 100],
            ['name' => 'Glossy Coated 200gsm',   'sku' => 'GLS-200',    'category' => 'Paper & Board',    'purchase_price' => 80,   'selling_price' => 110,  'stock_quantity' => 1500, 'reorder_level' => 150, 'material_type' => 'paper',  'gsm' => '200', 'paper_size' => 'A4', 'bundle_size' => 100],
            // Inks
            ['name' => 'CMYK Offset Ink Set',    'sku' => 'INK-CMY',    'category' => 'Inks & Chemicals',  'purchase_price' => 8500, 'selling_price' => 11000,'stock_quantity' => 20,   'reorder_level' => 5,   'material_type' => 'ink',    'base_unit' => 'kg'],
            ['name' => 'Black Offset Ink 2.5kg', 'sku' => 'INK-BLK',   'category' => 'Inks & Chemicals',  'purchase_price' => 1800, 'selling_price' => 2400, 'stock_quantity' => 15,   'reorder_level' => 4,   'material_type' => 'ink',    'base_unit' => 'tin'],
            ['name' => 'UV Varnish 5L',          'sku' => 'CHM-UV5',    'category' => 'Inks & Chemicals',  'purchase_price' => 3200, 'selling_price' => 4200, 'stock_quantity' => 8,    'reorder_level' => 2,   'material_type' => 'chemical','base_unit' => 'can'],
            ['name' => 'Fountain Solution',      'sku' => 'CHM-FNT',    'category' => 'Inks & Chemicals',  'purchase_price' => 950,  'selling_price' => 1300, 'stock_quantity' => 12,   'reorder_level' => 3,   'material_type' => 'chemical','base_unit' => 'bottle'],
            // Plates
            ['name' => 'CTP Plate 700×1000mm',  'sku' => 'PLT-CTP-L',  'category' => 'Plates & Films',    'purchase_price' => 350,  'selling_price' => 500,  'stock_quantity' => 100,  'reorder_level' => 20,  'material_type' => 'plate',  'base_unit' => 'pcs'],
            ['name' => 'CTP Plate 400×500mm',   'sku' => 'PLT-CTP-S',  'category' => 'Plates & Films',    'purchase_price' => 180,  'selling_price' => 260,  'stock_quantity' => 150,  'reorder_level' => 30,  'material_type' => 'plate',  'base_unit' => 'pcs'],
            // Packaging
            ['name' => 'Stretch Wrap Roll',     'sku' => 'PKG-STR',    'category' => 'Packaging Materials','purchase_price' => 850,  'selling_price' => 1100, 'stock_quantity' => 30,   'reorder_level' => 5,   'material_type' => 'packaging','base_unit' => 'roll'],
            ['name' => 'Corrugated Box 30×40', 'sku' => 'PKG-BOX',    'category' => 'Packaging Materials','purchase_price' => 45,   'selling_price' => 65,   'stock_quantity' => 500,  'reorder_level' => 100, 'material_type' => 'packaging','base_unit' => 'pcs'],
            // Consumables
            ['name' => 'Printing Blanket',      'sku' => 'CON-BLK',    'category' => 'Consumables',         'purchase_price' => 4500, 'selling_price' => 6000, 'stock_quantity' => 6,    'reorder_level' => 2,   'material_type' => 'other',  'base_unit' => 'pcs'],
            ['name' => 'Plate Developer 25L',   'sku' => 'CON-DEV',    'category' => 'Consumables',         'purchase_price' => 2800, 'selling_price' => 3800, 'stock_quantity' => 4,    'reorder_level' => 1,   'material_type' => 'chemical','base_unit' => 'drum'],
        ];

        foreach ($materials as $mat) {
            $category = $catMap[$mat['category']];
            [$product, $created] = [
                Product::firstOrCreate(
                    ['sku' => $mat['sku'], 'branch_id' => $branch->id],
                    [
                        'name'           => $mat['name'],
                        'category_id'    => $category->id,
                        'branch_id'      => $branch->id,
                        'purchase_price' => $mat['purchase_price'],
                        'selling_price'  => $mat['selling_price'],
                        'stock_quantity' => $mat['stock_quantity'],
                        'min_stock_level'=> $mat['reorder_level'] ?? 0,
                        'reorder_level'  => $mat['reorder_level'] ?? 0,
                        'base_unit'      => $mat['base_unit'] ?? 'sheet',
                        'product_type'   => 'product',
                        'material_type'  => $mat['material_type'],
                        'gsm'            => $mat['gsm'] ?? null,
                        'paper_size'     => $mat['paper_size'] ?? null,
                        'bundle_size'    => $mat['bundle_size'] ?? 0,
                        'is_active'      => true,
                    ]
                ),
                null,
            ];
            // Check if this was a newly created product (wasRecentlyCreated)
            if ($product->wasRecentlyCreated && $mat['stock_quantity'] > 0) {
                StockLedger::record(
                    $product,
                    'IN',
                    $mat['stock_quantity'],
                    $admin->id,
                    $branch->id,
                    'OPENING',
                    null,
                    'Opening stock — seeded'
                );
            }
        }

        // ── Suppliers ────────────────────────────────────────────────
        $suppliers = [
            ['name' => 'Ceylon Paper Mills Ltd', 'company' => 'Ceylon Paper Mills Ltd', 'phone' => '011-4567890', 'email' => 'sales@ceylanpaper.lk',   'address' => 'Ekala Industrial Zone', 'city' => 'Ja-Ela'],
            ['name' => 'Sun Chemical Lanka',     'company' => 'Sun Chemical Lanka',     'phone' => '011-5678901', 'email' => 'ink@sunchem.lk',          'address' => 'Biyagama EPZ',          'city' => 'Biyagama'],
            ['name' => 'Agfa Materials Lanka',   'company' => 'Agfa Materials Lanka',   'phone' => '011-6789012', 'email' => 'plates@agfa.lk',          'address' => 'Colombo 02',            'city' => 'Colombo'],
            ['name' => 'Express Packaging Ltd',  'company' => 'Express Packaging Ltd',  'phone' => '011-7890123', 'email' => 'info@expresspack.lk',     'address' => 'Ratmalana',             'city' => 'Ratmalana'],
            ['name' => 'Indo-Lanka Supplies',    'company' => 'Indo-Lanka Supplies',    'phone' => '077-1234567', 'email' => 'supplies@indolanka.lk',   'address' => 'Peliyagoda',            'city' => 'Peliyagoda'],
        ];

        foreach ($suppliers as $s) {
            Supplier::firstOrCreate(
                ['name' => $s['name'], 'branch_id' => $branch->id],
                array_merge($s, ['branch_id' => $branch->id, 'is_active' => true, 'country' => 'Sri Lanka'])
            );
        }

        // ── Customers ────────────────────────────────────────────────
        $customers = [
            ['name' => 'Dialog Axiata PLC',       'company_name' => 'Dialog Axiata PLC',        'phone' => '077-6780000', 'email' => 'print@dialog.lk',         'address' => 'Dialog House, Colombo 03', 'credit_limit' => 500000],
            ['name' => 'Hemas Holdings',           'company_name' => 'Hemas Holdings PLC',       'phone' => '011-4731731', 'email' => 'marketing@hemas.lk',      'address' => 'No.36 Bristol St, Col 01', 'credit_limit' => 300000],
            ['name' => 'Lanka Hospitals',          'company_name' => 'Lanka Hospitals Corp',     'phone' => '011-5430000', 'email' => 'admin@lankahospitals.lk', 'address' => 'Narahenpita, Colombo 05', 'credit_limit' => 200000],
            ['name' => 'Keells Retail (Pvt) Ltd', 'company_name' => 'John Keells Holdings',      'phone' => '011-2306000', 'email' => 'supply@keells.lk',        'address' => 'No.130 Glennie St, Col 02','credit_limit' => 400000],
            ['name' => 'Nestlé Lanka Ltd',        'company_name' => 'Nestlé Lanka Ltd',         'phone' => '011-4791000', 'email' => 'packaging@nestle.lk',     'address' => 'Kurunegala Rd, Polgahawela', 'credit_limit' => 250000],
            ['name' => 'Saman Perera',            'company_name' => null,                       'phone' => '077-1234567', 'email' => 'saman@gmail.com',          'address' => 'Nugegoda',                'credit_limit' => 50000],
            ['name' => 'Colombo Fashion Week',    'company_name' => 'CFW Events (Pvt) Ltd',     'phone' => '077-9876543', 'email' => 'info@cfw.lk',              'address' => 'Colombo 07',              'credit_limit' => 100000],
            ['name' => 'University of Colombo',   'company_name' => 'University of Colombo',    'phone' => '011-2583835', 'email' => 'registry@cmb.ac.lk',      'address' => 'College House, Col 03',   'credit_limit' => 150000],
        ];

        foreach ($customers as $c) {
            Customer::firstOrCreate(
                ['phone' => $c['phone'], 'branch_id' => $branch->id],
                [
                    'name'                => $c['name'],
                    'company_name'        => $c['company_name'],
                    'email'               => $c['email'],
                    'address'             => $c['address'],
                    'branch_id'           => $branch->id,
                    'credit_limit'        => $c['credit_limit'],
                    'outstanding_balance' => 0,
                ]
            );
        }

        // ── Client Portal User ───────────────────────────────────────
        $firstCustomer = Customer::where('branch_id', $branch->id)->first();
        if ($firstCustomer) {
            User::firstOrCreate(
                ['email' => 'client@lmucpress.lk'],
                [
                    'name'        => 'Portal Client',
                    'password'    => Hash::make('password'),
                    'role'        => 'client',
                    'branch_id'   => $branch->id,
                    'customer_id' => $firstCustomer->id,
                    'is_active'   => true,
                ]
            );
        }

        // ── Sample Quotations ────────────────────────────────────────
        $customer1 = Customer::where('branch_id', $branch->id)->first();
        if ($customer1 && !Quotation::where('branch_id', $branch->id)->exists()) {
            $qt = Quotation::create([
                'branch_id'             => $branch->id,
                'quotation_number'      => 'QT-20260606-0001',
                'customer_id'           => $customer1->id,
                'title'                 => 'Annual Report 2025 — 500 Copies',
                'product_type'          => 'annual_report',
                'paper_type'            => 'Art Paper',
                'gsm'                   => 170,
                'size'                  => 'A4',
                'width_mm'              => 210,
                'height_mm'             => 297,
                'quantity'              => 500,
                'color_count'           => 4,
                'printing_method'       => 'offset',
                'plate_cost'            => 12000,
                'paper_cost'            => 35000,
                'ink_cost'              => 8500,
                'finishing_cost'        => 15000,
                'labour_cost'           => 12000,
                'wastage_percent'       => 5,
                'profit_margin_percent' => 25,
                'tax_rate'              => 8,
                'valid_until'           => now()->addDays(30)->toDateString(),
                'status'                => 'sent',
                'created_by'            => $admin->id,
                'notes'                 => 'Includes soft-touch lamination on covers. Spiral binding.',
            ]);
            $qt->calculateTotals();
            $qt->save();

            QuotationItem::insert([
                ['quotation_id' => $qt->id, 'description' => 'Printing — 68 pages + 4 cover pages', 'quantity' => 500, 'unit_price' => 110, 'total' => 55000, 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()],
                ['quotation_id' => $qt->id, 'description' => 'Soft-touch lamination (covers)', 'quantity' => 500, 'unit_price' => 25, 'total' => 12500, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['quotation_id' => $qt->id, 'description' => 'Spiral binding', 'quantity' => 500, 'unit_price' => 30, 'total' => 15000, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        // ── Sample Job Cards ─────────────────────────────────────────
        $customer2 = Customer::where('branch_id', $branch->id)->skip(1)->first();
        $machine1  = $machineObjs[0] ?? null;
        if ($customer2 && $machine1 && !JobCard::where('branch_id', $branch->id)->exists()) {
            $jobs = [
                [
                    'title'           => 'Business Cards — Dialog Axiata',
                    'product_description' => '3.5" x 2" Business Cards, 350gsm, double side',
                    'paper_type'      => 'Art Card 350gsm',
                    'gsm'             => 350,
                    'size'            => '3.5x2 inch',
                    'quantity_ordered'=> 1000,
                    'color_count'     => '4+4',
                    'printing_method' => 'offset',
                    'status'          => 'printing',
                    'due_date'        => now()->addDays(3)->toDateString(),
                ],
                [
                    'title'           => 'A5 Brochure — Hemas Holdings',
                    'product_description' => 'A5 folded brochure, 8pp, 170gsm art',
                    'paper_type'      => 'Art Paper 170gsm',
                    'gsm'             => 170,
                    'size'            => 'A5',
                    'quantity_ordered'=> 5000,
                    'color_count'     => '4+0',
                    'printing_method' => 'offset',
                    'status'          => 'proof_approval',
                    'due_date'        => now()->addDays(7)->toDateString(),
                ],
                [
                    'title'           => 'Banner 6x3ft — CFW Events',
                    'product_description' => 'Outdoor vinyl banner, UV inks',
                    'paper_type'      => 'Vinyl 440gsm',
                    'gsm'             => 440,
                    'size'            => '6x3 ft',
                    'quantity_ordered'=> 10,
                    'color_count'     => '4+0',
                    'printing_method' => 'digital',
                    'status'          => 'ready',
                    'due_date'        => now()->addDays(1)->toDateString(),
                ],
                [
                    'title'           => 'Exam Booklets — University of Colombo',
                    'product_description' => 'A4 exam booklets, 20pp, staple bound',
                    'paper_type'      => 'Bond 80gsm',
                    'gsm'             => 80,
                    'size'            => 'A4',
                    'quantity_ordered'=> 2000,
                    'color_count'     => '1+1',
                    'printing_method' => 'offset',
                    'status'          => 'waiting',
                    'due_date'        => now()->addDays(14)->toDateString(),
                ],
            ];

            $customers = Customer::where('branch_id', $branch->id)->get();
            foreach ($jobs as $i => $job) {
                $customer = $customers[$i % $customers->count()] ?? $customer2;
                JobCard::create([
                    ...$job,
                    'branch_id'   => $branch->id,
                    'job_number'  => 'JC-20260606-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                    'customer_id' => $customer->id,
                    'machine_id'  => $machine1->id,
                    'order_date'  => now()->subDays(2)->toDateString(),
                    'artwork_status' => in_array($job['status'], ['printing', 'proof_approval', 'ready']) ? 'approved' : 'pending',
                    'qr_code'     => strtoupper(substr(md5($job['title']), 0, 10)),
                    'created_by'  => $admin->id,
                ]);
            }
        }

        $this->command->info('Printing Press seed data created successfully.');
    }
}
