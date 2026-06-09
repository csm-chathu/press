<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Press material classification
            $table->string('material_type')->nullable()->after('product_type');
            // paper, ink, plate, chemical, packaging, service, other
            $table->string('gsm')->nullable()->after('material_type');
            $table->string('paper_size')->nullable()->after('gsm');
            $table->decimal('reorder_level', 10, 2)->default(0)->after('stock_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['material_type', 'gsm', 'paper_size', 'reorder_level']);
        });
    }
};
