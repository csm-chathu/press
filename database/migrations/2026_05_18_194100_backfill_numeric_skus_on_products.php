<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')->orderBy('id')->each(function ($product) {
            DB::table('products')
                ->where('id', $product->id)
                ->update(['sku' => str_pad($product->id, 6, '0', STR_PAD_LEFT)]);
        });
    }

    public function down(): void
    {
        // Irreversible — original BAR-XXXXXXXX values are not stored
    }
};
