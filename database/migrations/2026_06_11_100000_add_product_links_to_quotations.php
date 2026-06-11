<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->foreignId('paper_product_id')->nullable()->constrained('products')->nullOnDelete()->after('paper_type');
            $table->foreignId('ink_product_id')->nullable()->constrained('products')->nullOnDelete()->after('paper_product_id');
            $table->foreignId('plate_product_id')->nullable()->constrained('products')->nullOnDelete()->after('ink_product_id');
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropForeign(['paper_product_id']);
            $table->dropForeign(['ink_product_id']);
            $table->dropForeign(['plate_product_id']);
            $table->dropColumn(['paper_product_id', 'ink_product_id', 'plate_product_id']);
        });
    }
};
