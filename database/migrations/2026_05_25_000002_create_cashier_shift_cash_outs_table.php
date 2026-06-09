<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cashier_shift_cash_outs')) {
            return;
        }
        Schema::create('cashier_shift_cash_outs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('cashier_shifts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('reason', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashier_shift_cash_outs');
    }
};
