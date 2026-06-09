<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('income_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['income', 'expense']);
            $table->date('entry_date');
            $table->string('category', 120)->nullable();
            $table->string('description');
            $table->decimal('amount', 14, 2);
            $table->string('payment_method', 40)->default('cash');
            $table->string('reference', 120)->nullable();
            $table->string('account_code', 30)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->timestamps();

            $table->index(['branch_id', 'entry_date']);
            $table->index(['type', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('income_expenses');
    }
};
