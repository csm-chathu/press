<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bottle_deposits', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            $table->foreignId('purchase_id')->nullable()->after('supplier_id')->constrained()->nullOnDelete();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE bottle_deposits MODIFY COLUMN type ENUM('collect','refund','credit','supplier_return') NOT NULL DEFAULT 'collect'");
            DB::statement("ALTER TABLE bottle_deposits MODIFY COLUMN status ENUM('collected','refunded','credited','pending','supplier_returned') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        Schema::table('bottle_deposits', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_id');
            $table->dropConstrainedForeignId('purchase_id');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE bottle_deposits MODIFY COLUMN type ENUM('collect','refund','credit') NOT NULL DEFAULT 'collect'");
            DB::statement("ALTER TABLE bottle_deposits MODIFY COLUMN status ENUM('collected','refunded','credited','pending') NOT NULL DEFAULT 'pending'");
        }
    }
};
