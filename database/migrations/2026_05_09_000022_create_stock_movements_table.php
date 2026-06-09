<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('movement_type', ['IN', 'OUT', 'DAMAGE', 'RETURN', 'TRANSFER', 'ADJUSTMENT', 'OPEN_BOTTLE', 'DEPOSIT_IN', 'DEPOSIT_OUT']);
            $table->string('reference_type', 80)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('quantity', 12, 3);
            $table->decimal('balance_after', 12, 3)->nullable();
            $table->string('unit', 30)->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('moved_at')->useCurrent();
            $table->timestamps();

            $table->index(['product_id', 'moved_at']);
            $table->index(['movement_type', 'moved_at']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
