<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('finishing_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('job_card_id');

            // Finishing operations (booleans)
            $table->boolean('cutting')->default(false);
            $table->boolean('folding')->default(false);
            $table->boolean('binding')->default(false);
            $table->boolean('lamination')->default(false);
            $table->boolean('uv_coating')->default(false);
            $table->boolean('foiling')->default(false);
            $table->boolean('die_cutting')->default(false);
            $table->boolean('packaging')->default(false);

            $table->string('lamination_type')->nullable(); // gloss, matte
            $table->string('binding_type')->nullable();   // saddle_stitch, perfect, spiral, hard_cover
            $table->text('other_instructions')->nullable();

            $table->string('status')->default('pending'); // pending, in_progress, completed
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('job_card_id')->references('id')->on('job_cards')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finishing_tasks');
    }
};
