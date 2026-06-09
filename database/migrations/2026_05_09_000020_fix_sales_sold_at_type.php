<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('UPDATE sales SET sold_at = created_at WHERE sold_at IS NULL');

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE sales MODIFY sold_at DATETIME NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE sales MODIFY sold_at VARCHAR(255) NULL');
        }
    }
};
