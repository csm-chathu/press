<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('delivery_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('delivery_number')->unique();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();  // sales.id
            $table->date('delivery_date');
            $table->string('delivery_method')->default('own_vehicle'); // own_vehicle, courier, customer_pickup
            $table->string('vehicle_details')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('status')->default('pending'); // pending, dispatched, delivered, partial, returned
            $table->unsignedInteger('total_quantity')->default(0);
            $table->unsignedInteger('delivered_quantity')->default(0);
            $table->text('delivery_address')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('dispatched_by')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
        });

        Schema::create('delivery_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_note_id');
            $table->unsignedBigInteger('job_card_id')->nullable();
            $table->string('description');
            $table->unsignedInteger('quantity_ordered')->default(0);
            $table->unsignedInteger('quantity_delivered')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('delivery_note_id')->references('id')->on('delivery_notes')->cascadeOnDelete();
            $table->foreign('job_card_id')->references('id')->on('job_cards')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_items');
        Schema::dropIfExists('delivery_notes');
    }
};
