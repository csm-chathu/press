<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('code', 20)->unique();
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('branches')->insert([
            'name' => 'Main Branch',
            'code' => 'MAIN',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $defaultBranchId = DB::table('branches')->where('code', 'MAIN')->value('id');

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('admin')->after('password');
            $table->foreignId('branch_id')->nullable()->after('role')->constrained('branches')->nullOnDelete();
        });

        foreach (['categories', 'suppliers', 'products', 'customers', 'sales', 'purchases'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->after('id')->constrained('branches')->cascadeOnDelete();
            });
        }

        foreach (['categories', 'suppliers', 'products', 'customers', 'sales', 'purchases', 'users'] as $tableName) {
            DB::table($tableName)->update(['branch_id' => $defaultBranchId]);
        }

        DB::table('users')->whereNull('role')->update(['role' => 'admin']);
    }

    public function down(): void
    {
        foreach (['categories', 'suppliers', 'products', 'customers', 'sales', 'purchases'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('branch_id');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropColumn('role');
        });

        Schema::dropIfExists('branches');
    }
};
