<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // For paper products: how many sheets per bundle (e.g. 100 for A4 reams)
            // 0 or null = not applicable (ink, plates, etc.)
            $table->unsignedInteger('bundle_size')->default(0)->after('base_unit');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('bundle_size');
        });
    }
};
