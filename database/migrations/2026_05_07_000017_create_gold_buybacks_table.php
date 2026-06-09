<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gold_buybacks', function (Blueprint $table) {
            $table->id();
            $table->string('buyback_number')->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('gold_rate_id')->nullable()->constrained()->nullOnDelete();

            // Item details
            $table->string('description');
            $table->enum('item_type', ['jewelry', 'coin', 'bar', 'scrap', 'other'])->default('jewelry');
            $table->decimal('gross_weight', 10, 3)->default(0);     // total weight including stones
            $table->decimal('deduction_weight', 10, 3)->default(0); // stones, solder, clasps
            $table->decimal('net_weight', 10, 3)->default(0);       // gold weight after deductions
            $table->enum('declared_karat', ['9k', '14k', '18k', '22k', '24k', 'unknown'])->default('unknown');

            // Assay / Testing
            $table->enum('assay_method', ['visual', 'acid', 'xrf', 'fire_assay'])->nullable();
            $table->decimal('assay_karat', 5, 2)->nullable();   // actual purity from test e.g. 18.2
            $table->decimal('xrf_reading', 8, 4)->nullable();   // XRF purity e.g. 0.7583
            $table->decimal('melt_loss_percent', 5, 2)->nullable(); // estimated melt loss %
            $table->text('assay_notes')->nullable();

            // Pricing
            $table->decimal('rate_per_gram', 12, 2)->default(0);        // gold rate used
            $table->decimal('buying_price_per_gram', 12, 2)->default(0); // our offer per gram
            $table->decimal('offered_total', 12, 2)->default(0);
            $table->decimal('final_price', 12, 2)->default(0);
            $table->enum('payment_method', ['cash', 'bank_transfer', 'cheque'])->default('cash');

            // Compliance
            $table->boolean('kyc_verified')->default(false);

            // Status & notes
            $table->enum('status', ['pending', 'approved', 'completed', 'rejected'])->default('pending');
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gold_buybacks');
    }
};
