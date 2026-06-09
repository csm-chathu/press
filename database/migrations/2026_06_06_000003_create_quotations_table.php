<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('quotation_number')->unique();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('title');

            // Print job specs
            $table->string('product_type')->nullable();      // business_cards, brochures, banners, etc.
            $table->string('paper_type')->nullable();        // art paper, bond, newsprint, etc.
            $table->unsignedSmallInteger('gsm')->nullable(); // paper weight
            $table->string('size')->nullable();              // A4, A3, custom, etc.
            $table->decimal('width_mm', 8, 2)->nullable();
            $table->decimal('height_mm', 8, 2)->nullable();
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedTinyInteger('color_count')->default(4); // 1, 2, 4
            $table->string('printing_method')->nullable();   // offset, digital, screen, flexo, letterpress

            // Cost breakdown
            $table->decimal('plate_cost', 12, 2)->default(0);
            $table->decimal('paper_cost', 12, 2)->default(0);
            $table->decimal('ink_cost', 12, 2)->default(0);
            $table->decimal('finishing_cost', 12, 2)->default(0);
            $table->decimal('labour_cost', 12, 2)->default(0);
            $table->decimal('wastage_percent', 5, 2)->default(5);
            $table->decimal('profit_margin_percent', 5, 2)->default(20);

            // Totals
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            // Meta
            $table->string('status')->default('draft'); // draft, sent, approved, rejected, converted
            $table->date('valid_until')->nullable();
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
        });

        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quotation_id');
            $table->string('description');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('quotation_id')->references('id')->on('quotations')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
    }
};
