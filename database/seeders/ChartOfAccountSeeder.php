<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class ChartOfAccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['code' => '1000', 'name' => 'Cash On Hand', 'type' => 'asset'],
            ['code' => '1100', 'name' => 'Accounts Receivable', 'type' => 'asset'],
            ['code' => '1200', 'name' => 'Inventory', 'type' => 'asset'],
            ['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability'],
            ['code' => '2100', 'name' => 'Tax Payable', 'type' => 'liability'],
            ['code' => '2200', 'name' => 'Bottle Deposit Liability', 'type' => 'liability'],
            ['code' => '3000', 'name' => 'Owner Equity', 'type' => 'equity'],
            ['code' => '4000', 'name' => 'Sales Revenue', 'type' => 'revenue'],
            ['code' => '4050', 'name' => 'Sales Discounts', 'type' => 'revenue'],
            ['code' => '4100', 'name' => 'Other Income', 'type' => 'revenue'],
            ['code' => '5000', 'name' => 'Cost Of Goods Sold', 'type' => 'expense'],
            ['code' => '5100', 'name' => 'Damage Expense', 'type' => 'expense'],
            ['code' => '5200', 'name' => 'Salary Expense', 'type' => 'expense'],
            ['code' => '5300', 'name' => 'Operating Expense', 'type' => 'expense'],
        ];

        foreach ($accounts as $account) {
            Account::updateOrCreate(
                ['branch_id' => null, 'code' => $account['code']],
                [
                    'name' => $account['name'],
                    'type' => $account['type'],
                    'is_active' => true,
                ]
            );
        }
    }
}
