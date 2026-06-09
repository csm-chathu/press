<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();
        $rows = [
            ['branch_id' => null, 'code' => '4100', 'name' => 'Other Income', 'type' => 'revenue', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['branch_id' => null, 'code' => '5200', 'name' => 'Salary Expense', 'type' => 'expense', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['branch_id' => null, 'code' => '5300', 'name' => 'Operating Expense', 'type' => 'expense', 'is_active' => 1, 'created_at' => $now, 'updated_at' => $now],
        ];

        foreach ($rows as $row) {
            DB::table('accounts')->updateOrInsert(
                ['branch_id' => $row['branch_id'], 'code' => $row['code']],
                $row
            );
        }
    }

    public function down(): void
    {
        DB::table('accounts')->whereNull('branch_id')->whereIn('code', ['4100', '5200', '5300'])->delete();
    }
};
