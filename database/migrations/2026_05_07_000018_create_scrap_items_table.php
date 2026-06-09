<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scrap_items', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('description');
            $table->enum('source_type', ['buyback', 'converted_product', 'other'])->default('buyback');
            $table->foreignId('buyback_id')->nullable()->constrained('gold_buybacks')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete(); // original product if converted
            $table->foreignId('gold_rate_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();

            $table->enum('karat', ['9k', '14k', '18k', '22k', '24k', 'mixed'])->default('mixed');
            $table->decimal('weight_g', 10, 3)->default(0);
            $table->decimal('estimated_value', 12, 2)->default(0);

            $table->enum('status', ['available', 'sent_for_refining', 'melted', 'sold'])->default('available');
            $table->string('refinery_name')->nullable();
            $table->text('refinery_notes')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scrap_items');
    }
};
