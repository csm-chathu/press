<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // No-op: the create_cashier_shifts_table migration (2026_05_25_000001)
        // already defines branch_id as nullable. This migration runs first due to
        // its earlier timestamp but the table does not exist yet at that point.
        if (!Schema::hasTable('cashier_shifts')) {
            return;
        }

        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->unsignedBigInteger('branch_id')->nullable()->change();
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('cashier_shifts')) {
            return;
        }

        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->unsignedBigInteger('branch_id')->nullable(false)->change();
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
        });
    }
};
