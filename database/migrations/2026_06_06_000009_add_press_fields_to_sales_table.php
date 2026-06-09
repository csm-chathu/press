<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedBigInteger('quotation_id')->nullable()->after('customer_id');
            $table->string('order_type')->default('direct')->after('status'); // direct, from_quotation
            $table->string('artwork_status')->default('not_required')->after('order_type');
            // not_required, pending, received, approved
            $table->decimal('advance_payment', 12, 2)->default(0)->after('artwork_status');
            $table->date('delivery_date')->nullable()->after('advance_payment');
            $table->string('order_status')->default('new')->after('delivery_date');
            // new, in_production, ready, delivered, cancelled
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['quotation_id', 'order_type', 'artwork_status', 'advance_payment', 'delivery_date', 'order_status']);
        });
    }
};
