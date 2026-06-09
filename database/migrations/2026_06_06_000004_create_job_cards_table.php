<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('job_cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('job_number')->unique();
            $table->unsignedBigInteger('order_id')->nullable();      // sales.id
            $table->unsignedBigInteger('quotation_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('title');
            $table->text('product_description')->nullable();

            // Material specs
            $table->string('paper_type')->nullable();
            $table->unsignedSmallInteger('gsm')->nullable();
            $table->string('size')->nullable();
            $table->decimal('width_mm', 8, 2)->nullable();
            $table->decimal('height_mm', 8, 2)->nullable();
            $table->unsignedInteger('quantity_ordered')->default(0);
            $table->string('color_count')->nullable();
            $table->string('printing_method')->nullable();

            // Instructions
            $table->text('printing_instructions')->nullable();
            $table->text('finishing_instructions')->nullable();
            $table->text('delivery_instructions')->nullable();

            // Assignment
            $table->unsignedBigInteger('machine_id')->nullable();
            $table->unsignedBigInteger('assigned_operator_id')->nullable();

            // Artwork / Pre-press
            $table->string('artwork_status')->default('pending'); // pending, received, reviewing, approved
            $table->string('artwork_file_path')->nullable();

            // Production status
            $table->string('status')->default('waiting');
            // waiting, designing, proof_approval, plate_making, printing, finishing, quality_check, ready, delivered

            // Dates
            $table->date('order_date')->nullable();
            $table->date('scheduled_date')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->string('qr_code')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
            $table->foreign('machine_id')->references('id')->on('press_machines')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_cards');
    }
};
