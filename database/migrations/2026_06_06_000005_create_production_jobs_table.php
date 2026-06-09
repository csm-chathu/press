<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('production_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('job_card_id');
            $table->unsignedBigInteger('machine_id')->nullable();
            $table->unsignedBigInteger('operator_id')->nullable(); // users.id
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->unsignedInteger('output_quantity')->default(0);
            $table->unsignedInteger('waste_quantity')->default(0);
            $table->string('status')->default('pending'); // pending, in_progress, paused, completed
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('job_card_id')->references('id')->on('job_cards')->cascadeOnDelete();
            $table->foreign('machine_id')->references('id')->on('press_machines')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_jobs');
    }
};
