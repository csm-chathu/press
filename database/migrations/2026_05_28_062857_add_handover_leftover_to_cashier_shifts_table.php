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
        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->decimal('handover_amount', 12, 2)->nullable()->after('closing_cash');
            $table->decimal('leftover_amount', 12, 2)->nullable()->after('handover_amount');
        });
    }

    public function down(): void
    {
        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->dropColumn(['handover_amount', 'leftover_amount']);
        });
    }
};
