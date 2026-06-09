<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->enum('id_type', ['nic', 'passport', 'driving_license', 'other'])->nullable()->after('notes');
            $table->string('id_number')->nullable()->after('id_type');
            $table->date('id_expiry')->nullable()->after('id_number');
            $table->boolean('kyc_verified')->default(false)->after('id_expiry');
            $table->text('kyc_notes')->nullable()->after('kyc_verified');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['id_type', 'id_number', 'id_expiry', 'kyc_verified', 'kyc_notes']);
        });
    }
};
