<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ChartOfAccountSeeder::class);
        $this->call(PrintingPressSeeder::class);

        $mainBranch = Branch::firstOrCreate(
            ['code' => 'MAIN'],
            ['name' => 'Main Branch', 'is_active' => true]
        );

        $downtownBranch = Branch::firstOrCreate(
            ['code' => 'DWTN'],
            ['name' => 'Downtown Branch', 'is_active' => true]
        );

        $starterUsers = [
            ['name' => 'Owner',         'email' => 'owner@store.local',        'role' => 'owner',        'branch_id' => $mainBranch->id],
            ['name' => 'Admin',         'email' => 'admin@store.local',        'role' => 'admin',        'branch_id' => $mainBranch->id],
            ['name' => 'Manager',       'email' => 'manager@store.local',      'role' => 'manager',      'branch_id' => $mainBranch->id],
            ['name' => 'Cashier',       'email' => 'cashier@store.local',      'role' => 'cashier',      'branch_id' => $mainBranch->id],
            ['name' => 'Store Keeper',  'email' => 'keeper@store.local',       'role' => 'store_keeper', 'branch_id' => $downtownBranch->id],
        ];

        foreach ($starterUsers as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name'      => $user['name'],
                    'password'  => Hash::make('password'),
                    'role'      => $user['role'],
                    'branch_id' => $user['branch_id'],
                    'can_override_gold_rate' => in_array($user['role'], ['admin', 'owner', 'manager'], true),
                    'can_delete_transactions' => in_array($user['role'], ['admin', 'owner'], true),
                    'is_active' => true,
                ]
            );
        }

        $categories = [
            ['name' => 'Liquor',     'slug' => 'liquor'],
            ['name' => 'Beer',       'slug' => 'beer'],
            ['name' => 'Soft Drinks','slug' => 'soft-drinks'],
            ['name' => 'Food',       'slug' => 'food'],
            ['name' => 'Snacks',     'slug' => 'snacks'],
            ['name' => 'Accessories','slug' => 'accessories'],
        ];
        foreach ($categories as $cat) {
            Category::firstOrCreate(
                ['slug' => $cat['slug'], 'branch_id' => $mainBranch->id],
                array_merge($cat, ['is_active' => true, 'branch_id' => $mainBranch->id])
            );
        }

        $suppliers = [
            ['name' => 'Gold Masters Ltd',    'email' => 'info@goldmasters.com',    'phone' => '+1-800-111-2222', 'city' => 'New York',    'country' => 'USA'],
            ['name' => 'Silver Craft Co.',    'email' => 'orders@silvercraft.com',  'phone' => '+44-20-1234-5678','city' => 'London',      'country' => 'UK'],
            ['name' => 'Diamond Hub',         'email' => 'contact@diamondhub.com',  'phone' => '+91-98765-43210', 'city' => 'Mumbai',      'country' => 'India'],
            ['name' => 'Precious Stone Inc.', 'email' => 'sales@preciousstone.com', 'phone' => '+1-888-333-4444', 'city' => 'Los Angeles', 'country' => 'USA'],
        ];
        foreach ($suppliers as $sup) {
            Supplier::updateOrCreate(
                ['email' => $sup['email']],
                array_merge($sup, ['is_active' => true, 'branch_id' => $mainBranch->id])
            );
        }

        $categoryIds = Category::pluck('id')->toArray();
        $supplierIds = Supplier::pluck('id')->toArray();
        $products = [
            ['name' => 'Johnnie Walker Black Label 750ml', 'category' => 'Liquor', 'type' => 'Liquor', 'brand' => 'Johnnie Walker', 'unit_type' => 'Bottle', 'base_unit' => '750ml', 'variants' => '30ml, 50ml, 60ml, 750ml', 'deposit' => false, 'purchase' => 18500, 'sell' => 22900],
            ['name' => 'Smirnoff Vodka 750ml',              'category' => 'Liquor', 'type' => 'Liquor', 'brand' => 'Smirnoff',      'unit_type' => 'Bottle', 'base_unit' => '750ml', 'variants' => '30ml, 50ml, 750ml', 'deposit' => false, 'purchase' => 9500,  'sell' => 12400],
            ['name' => 'Heineken Bottle',                   'category' => 'Beer',   'type' => 'Beer',   'brand' => 'Heineken',       'unit_type' => 'Bottle', 'base_unit' => '330ml', 'variants' => 'Bottle, Case', 'deposit' => true,  'purchase' => 260,   'sell' => 390],
            ['name' => 'Lion Lager Bottle',                 'category' => 'Beer',   'type' => 'Beer',   'brand' => 'Lion',           'unit_type' => 'Bottle', 'base_unit' => '330ml', 'variants' => 'Bottle, Case', 'deposit' => true,  'purchase' => 230,   'sell' => 360],
            ['name' => 'Coca-Cola Can',                     'category' => 'Soft Drinks','type' => 'Soft Drinks', 'brand' => 'Coca-Cola', 'unit_type' => 'Can',    'base_unit' => '330ml', 'variants' => 'Can, Pack', 'deposit' => false, 'purchase' => 120,   'sell' => 180],
            ['name' => 'Sprite Bottle',                     'category' => 'Soft Drinks','type' => 'Soft Drinks', 'brand' => 'Sprite',    'unit_type' => 'Bottle', 'base_unit' => '500ml', 'variants' => 'Bottle, Pack', 'deposit' => false, 'purchase' => 90,    'sell' => 150],
            ['name' => 'Chicken Kottu',                     'category' => 'Food',    'type' => 'Food',    'brand' => null,           'unit_type' => 'Plate',  'base_unit' => '1 serving', 'variants' => 'Plate', 'deposit' => false, 'purchase' => 350,  'sell' => 650],
            ['name' => 'Fish & Chips',                      'category' => 'Food',    'type' => 'Food',    'brand' => null,           'unit_type' => 'Plate',  'base_unit' => '1 serving', 'variants' => 'Plate', 'deposit' => false, 'purchase' => 420,  'sell' => 780],
            ['name' => 'Peanuts Pack',                      'category' => 'Snacks',  'type' => 'Food',    'brand' => null,           'unit_type' => 'Pack',   'base_unit' => '1 pack', 'variants' => 'Pack', 'deposit' => false, 'purchase' => 60,    'sell' => 120],
            ['name' => 'Bar Straw Pack',                    'category' => 'Accessories','type' => 'Accessories', 'brand' => null,    'unit_type' => 'Pack',   'base_unit' => '100 pcs', 'variants' => 'Pack', 'deposit' => false, 'purchase' => 180,  'sell' => 300],
        ];

        foreach ($products as $index => $product) {
            $categorySlug = str($product['category'])->slug()->toString();
            $categoryId = Category::where('slug', $categorySlug)->value('id') ?: $categoryIds[$index % count($categoryIds)];

            Product::create([
                'sku'                    => 'BAR-' . strtoupper(Str::random(8)),
                'name'                   => $product['name'],
                'category_id'            => $categoryId,
                'product_type'           => $product['type'],
                'brand'                  => $product['brand'],
                'unit_type'              => $product['unit_type'],
                'base_unit'              => $product['base_unit'],
                'selling_variants'       => $product['variants'],
                'bottle_deposit_required'=> $product['deposit'],
                'purchase_price'         => $product['purchase'],
                'selling_price'          => $product['sell'],
                'stock_quantity'         => mt_rand(10, 80),
                'min_stock_level'        => 5,
                'supplier_id'            => $supplierIds[array_rand($supplierIds)],
                'is_active'              => true,
                'branch_id'              => $mainBranch->id,
            ]);
        }

        $customers = [
            ['name' => 'Alice Johnson',  'email' => 'alice@example.com',  'phone' => '+1-555-001'],
            ['name' => 'Bob Smith',      'email' => 'bob@example.com',    'phone' => '+1-555-002'],
            ['name' => 'Carol Williams', 'email' => 'carol@example.com',  'phone' => '+1-555-003'],
            ['name' => 'David Brown',    'email' => 'david@example.com',  'phone' => '+1-555-004'],
            ['name' => 'Emma Davis',     'email' => 'emma@example.com',   'phone' => '+1-555-005'],
        ];
        foreach ($customers as $cust) {
            Customer::updateOrCreate(
                ['email' => $cust['email']],
                array_merge($cust, ['branch_id' => $mainBranch->id])
            );
        }
    }
}
