<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['shot_category', 'shot_price']);
            $table->json('shot_variants')->nullable()->after('selling_variants');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('shot_variants');
            $table->string('shot_category')->nullable()->after('selling_variants');
            $table->decimal('shot_price', 10, 2)->nullable()->after('shot_category');
        });
    }
};
