<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('appointment_tracking', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('reason');
            $table->json('symptoms');
            $table->text('diagnosis');
            $table->text('procedure_performed');
            $table->text('observations')->nullable();
            $table->text('recommendations')->nullable();
            $table->foreignUuid('appointment_id')
                ->unique()
                ->constrained('appointments')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->timestamps();
        });

        Schema::create('appointment_tracking_prescriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('medication');
            $table->string('dosage');
            $table->unsignedInteger('duration_days');
            $table->unsignedInteger('daily_frequency');
            $table->text('instructions')->nullable();
            $table->foreignUuid('appointment_tracking_id')
                ->constrained('appointment_tracking')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_tracking_prescriptions');
        Schema::dropIfExists('appointment_tracking');
    }
};
