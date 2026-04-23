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
        Schema::table('appointments', function (Blueprint $table) {
            // Delete the 'pattien_id' field and add a new 'patient_id' field with the correct spelling
            $table->dropForeign(['patient_id']);
            $table->dropColumn('patient_id');

            // Add the new 'patient_id' field with the correct spelling and set up the foreign key constraint and datatype uuid
            $table->foreignUuid('patient_id')->references('id')->on('patients')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropColumn('patient_id');

            // Re-add the old 'pattien_id' field with the incorrect spelling and set up the foreign key constraint and datatype bigInteger
            $table->foreignId('pattien_id')->references('id')->on('patients')->onDelete('cascade')->onUpdate('cascade');
        });
    }
};
