<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // e.g. "VAT", "GST"
            $table->decimal('rate', 5, 2);                // percentage, e.g. 15.00
            $table->enum('applies_to', ['all', 'gold_only', 'gemstone_only', 'making_charges'])->default('all');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Seed a default VAT
        DB::table('tax_settings')->insert([
            'name'        => 'VAT',
            'rate'        => 18.00,
            'applies_to'  => 'all',
            'is_active'   => true,
            'description' => 'Value Added Tax on jewellery',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_settings');
    }
};
