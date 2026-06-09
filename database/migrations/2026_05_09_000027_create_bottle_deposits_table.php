<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bottle_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['collect', 'refund', 'credit'])->default('collect');
            $table->enum('status', ['collected', 'refunded', 'credited', 'pending'])->default('pending');
            $table->decimal('quantity', 12, 3)->default(1);
            $table->decimal('amount_per_bottle', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('processed_at')->useCurrent();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['type', 'processed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bottle_deposits');
    }
};
