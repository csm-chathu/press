<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('press_machines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('name');
            $table->string('machine_type'); // printing, cutting, binding, lamination, uv, folding, die_cutting
            $table->string('model_number')->nullable();
            $table->string('manufacturer')->nullable();
            $table->unsignedInteger('capacity_per_hour')->nullable();
            $table->string('status')->default('active'); // active, maintenance, inactive
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('press_machines');
    }
};
