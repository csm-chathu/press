<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('accounts')->updateOrInsert(
            ['branch_id' => null, 'code' => '4050'],
            [
                'name'       => 'Sales Discounts',
                'type'       => 'revenue',
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('accounts')
            ->whereNull('branch_id')
            ->where('code', '4050')
            ->delete();
    }
};
