<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        // Chart of accounts — required for accounting to function
        $this->call(ChartOfAccountSeeder::class);

        // Default branch
        $branch = Branch::firstOrCreate(
            ['code' => 'MAIN'],
            ['name' => 'Main Branch', 'is_active' => true]
        );

        // One user per role — change credentials after first login
        $users = [
            ['name' => 'Owner',       'email' => 'owner@store.local',   'role' => 'owner',        'can_delete_transactions' => true],
            ['name' => 'Admin',       'email' => 'admin@store.local',   'role' => 'admin',        'can_delete_transactions' => true],
            ['name' => 'Manager',     'email' => 'manager@store.local', 'role' => 'manager',      'can_delete_transactions' => false],
            ['name' => 'Cashier',     'email' => 'cashier@store.local', 'role' => 'cashier',      'can_delete_transactions' => false],
            ['name' => 'Store Keeper','email' => 'keeper@store.local',  'role' => 'store_keeper', 'can_delete_transactions' => false],
        ];

        foreach ($users as $u) {
            User::firstOrCreate(
                ['email' => $u['email']],
                [
                    'name'                    => $u['name'],
                    'password'                => Hash::make('password'),
                    'role'                    => $u['role'],
                    'branch_id'               => $branch->id,
                    'can_delete_transactions' => $u['can_delete_transactions'],
                    'is_active'               => true,
                ]
            );
        }
    }
}
