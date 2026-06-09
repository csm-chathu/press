<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('day_end_reports', function (Blueprint $table) {
            $table->id();
            $table->date('report_date')->unique();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->decimal('system_gold_weight', 12, 3)->default(0);   // grams from system
            $table->decimal('physical_gold_weight', 12, 3)->nullable(); // actual count
            $table->decimal('variance_weight', 12, 3)->nullable();      // difference
            $table->decimal('system_stock_value', 14, 2)->default(0);   // LKR from system
            $table->decimal('physical_stock_value', 14, 2)->nullable(); // LKR from count
            $table->json('karat_breakdown')->nullable();                 // per-karat summary
            $table->json('item_counts')->nullable();                     // per-product physical count
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'submitted'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('day_end_reports');
    }
};
