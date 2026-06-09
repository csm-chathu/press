<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('products', 'making_charge_type')) {
            Schema::table('products', function (Blueprint $table) {
                $table->enum('making_charge_type', ['per_gram', 'per_piece', 'percentage'])->default('per_gram');
            });
        }
        if (!Schema::hasColumn('products', 'making_charge')) {
            Schema::table('products', function (Blueprint $table) {
                $table->decimal('making_charge', 10, 2)->default(0);
            });
        }
        if (!Schema::hasColumn('products', 'wastage_percent')) {
            Schema::table('products', function (Blueprint $table) {
                $table->decimal('wastage_percent', 5, 2)->default(0);
            });
        }
        if (!Schema::hasColumn('products', 'gemstone_weight')) {
            Schema::table('products', function (Blueprint $table) {
                $table->decimal('gemstone_weight', 8, 3)->nullable();
            });
        }
        if (!Schema::hasColumn('products', 'gemstone_value')) {
            Schema::table('products', function (Blueprint $table) {
                $table->decimal('gemstone_value', 12, 2)->default(0);
            });
        }
        if (!Schema::hasColumn('products', 'gemstone_quality')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('gemstone_quality')->nullable();
            });
        }

        if (!Schema::hasColumn('sale_items', 'gold_value')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->decimal('gold_value', 12, 2)->default(0);
            });
        }
        if (!Schema::hasColumn('sale_items', 'gemstone_value')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->decimal('gemstone_value', 12, 2)->default(0);
            });
        }
        if (!Schema::hasColumn('sale_items', 'making_charge')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->decimal('making_charge', 12, 2)->default(0);
            });
        }
        if (!Schema::hasColumn('sale_items', 'wastage_amount')) {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->decimal('wastage_amount', 12, 2)->default(0);
            });
        }

        if (!Schema::hasColumn('sales', 'gold_value_total')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->decimal('gold_value_total', 14, 2)->default(0);
            });
        }
        if (!Schema::hasColumn('sales', 'gemstone_value_total')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->decimal('gemstone_value_total', 14, 2)->default(0);
            });
        }
        if (!Schema::hasColumn('sales', 'making_charges_total')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->decimal('making_charges_total', 14, 2)->default(0);
            });
        }
        if (!Schema::hasColumn('sales', 'wastage_total')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->decimal('wastage_total', 14, 2)->default(0);
            });
        }
        if (!Schema::hasColumn('sales', 'tax_rate')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->decimal('tax_rate', 5, 2)->default(0);
            });
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['making_charge_type','making_charge','wastage_percent','gemstone_weight','gemstone_value','gemstone_quality']);
        });
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['gold_value','gemstone_value','making_charge','wastage_amount']);
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['gold_value_total','gemstone_value_total','making_charges_total','wastage_total','tax_rate']);
        });
    }
};
