<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quotation_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('product_type')->nullable();
            $table->string('paper_type')->nullable();
            $table->unsignedSmallInteger('gsm')->nullable();
            $table->string('size')->nullable();
            $table->decimal('width_mm', 8, 2)->nullable();
            $table->decimal('height_mm', 8, 2)->nullable();
            $table->unsignedTinyInteger('color_count')->default(4);
            $table->string('printing_method')->nullable();
            $table->decimal('plate_cost', 12, 2)->default(0);
            $table->decimal('paper_cost', 12, 2)->default(0);
            $table->decimal('ink_cost', 12, 2)->default(0);
            $table->decimal('finishing_cost', 12, 2)->default(0);
            $table->decimal('labour_cost', 12, 2)->default(0);
            $table->decimal('wastage_percent', 5, 2)->default(5);
            $table->decimal('profit_margin_percent', 5, 2)->default(20);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_templates');
    }
};
