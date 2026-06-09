<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('job_costing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_card_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();

            // Paper
            $table->integer('paper_sheets')->default(0);
            $table->decimal('paper_rate_per_sheet', 10, 4)->default(0);
            $table->decimal('paper_cost', 12, 2)->default(0);

            // Ink
            $table->integer('ink_colours')->default(0);
            $table->decimal('ink_cost_per_colour', 10, 2)->default(0);
            $table->decimal('ink_cost', 12, 2)->default(0);

            // Plate
            $table->integer('plate_count')->default(0);
            $table->decimal('plate_cost_each', 10, 2)->default(0);
            $table->decimal('plate_cost', 12, 2)->default(0);

            // Machine
            $table->decimal('machine_hours', 8, 2)->default(0);
            $table->decimal('machine_rate_per_hour', 10, 2)->default(0);
            $table->decimal('machine_cost', 12, 2)->default(0);

            // Labour
            $table->decimal('labour_hours', 8, 2)->default(0);
            $table->decimal('labour_rate_per_hour', 10, 2)->default(0);
            $table->decimal('labour_cost', 12, 2)->default(0);

            // Fixed costs
            $table->decimal('electricity_cost', 12, 2)->default(0);
            $table->decimal('outsource_cost', 12, 2)->default(0);
            $table->text('outsource_description')->nullable();

            // Waste
            $table->decimal('waste_percentage', 5, 2)->default(0);
            $table->decimal('waste_cost', 12, 2)->default(0);

            // Totals
            $table->decimal('total_actual_cost', 12, 2)->default(0);

            // Profitability
            $table->decimal('revenue', 12, 2)->default(0);
            $table->decimal('profit', 12, 2)->default(0);
            $table->decimal('profit_margin', 6, 2)->default(0);

            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_costing');
    }
};
