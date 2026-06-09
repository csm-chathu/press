<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('prepress_tasks', function (Blueprint $table) {
            $table->enum('client_decision', ['approved', 'rejected'])->nullable()->after('revision_notes');
            $table->timestamp('client_decision_at')->nullable()->after('client_decision');
            $table->text('client_notes')->nullable()->after('client_decision_at');
        });
    }

    public function down(): void
    {
        Schema::table('prepress_tasks', function (Blueprint $table) {
            $table->dropColumn(['client_decision', 'client_decision_at', 'client_notes']);
        });
    }
};
