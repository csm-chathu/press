<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('can_override_gold_rate')->default(false)->after('role');
            $table->boolean('can_delete_transactions')->default(false)->after('can_override_gold_rate');
            $table->boolean('is_active')->default(true)->after('can_delete_transactions');
        });

        // Grant admin full permissions
        DB::table('users')->where('role', 'admin')->update([
            'can_override_gold_rate'    => true,
            'can_delete_transactions'   => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['can_override_gold_rate','can_delete_transactions','is_active']);
        });
    }
};
