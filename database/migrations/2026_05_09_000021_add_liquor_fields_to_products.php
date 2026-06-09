<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('products', 'product_type')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('product_type', 50)->default('liquor')->after('category_id');
            });
        }
        if (!Schema::hasColumn('products', 'brand')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('brand', 100)->nullable();
            });
        }
        if (!Schema::hasColumn('products', 'unit_type')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('unit_type', 50)->nullable();
            });
        }
        if (!Schema::hasColumn('products', 'base_unit')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('base_unit', 50)->nullable();
            });
        }
        if (!Schema::hasColumn('products', 'selling_variants')) {
            Schema::table('products', function (Blueprint $table) {
                $table->text('selling_variants')->nullable();
            });
        }
        if (!Schema::hasColumn('products', 'bottle_deposit_required')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('bottle_deposit_required')->default(false);
            });
        }
        if (!Schema::hasColumn('products', 'tax_setting_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreignId('tax_setting_id')->nullable()->after('supplier_id')->constrained('tax_settings')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tax_setting_id');
            $table->dropColumn([
                'product_type',
                'brand',
                'unit_type',
                'base_unit',
                'selling_variants',
                'bottle_deposit_required',
            ]);
        });
    }
};