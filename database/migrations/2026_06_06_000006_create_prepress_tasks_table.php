<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('prepress_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('job_card_id');

            // Artwork
            $table->string('artwork_file_path')->nullable();
            $table->string('artwork_filename')->nullable();
            $table->timestamp('artwork_uploaded_at')->nullable();
            $table->unsignedBigInteger('artwork_uploaded_by')->nullable();

            // Proofing
            $table->timestamp('proof_sent_at')->nullable();
            $table->timestamp('proof_approved_at')->nullable();
            $table->unsignedBigInteger('proof_approved_by')->nullable();
            $table->unsignedTinyInteger('revision_count')->default(0);
            $table->text('revision_notes')->nullable();

            // Plate making
            $table->string('plate_status')->default('not_started'); // not_started, in_progress, completed
            $table->timestamp('plate_completed_at')->nullable();
            $table->unsignedTinyInteger('plate_count')->default(0);

            $table->string('status')->default('pending');
            // pending, artwork_received, proof_sent, revision_requested, proof_approved, plates_ready
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('job_card_id')->references('id')->on('job_cards')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prepress_tasks');
    }
};
