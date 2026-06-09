<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('name');
            $table->decimal('credit_limit', 12, 2)->default(0)->after('notes');
            $table->decimal('outstanding_balance', 12, 2)->default(0)->after('credit_limit');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['company_name', 'credit_limit', 'outstanding_balance']);
        });
    }
};
