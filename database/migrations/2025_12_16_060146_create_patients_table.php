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
        
        Schema::create('medical_data', function (Blueprint $table) {
            $table->id();
            $table->string('blood_type')->nullable();
            $table->json('allergies')->nullable();
            $table->json('medications')->nullable();
            $table->json('last_dentist_visit')->nullable();
            $table->timestamps();
        });

        Schema::create('contact_info', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->timestamps();
        });
        
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('address')->nullable();
            $table->enum('status', ['active', 'inactive'])->nullable();
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('medical_data_id')->constrained('medical_data')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('contact_info_id')->constrained('contact_info')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('address_id')->constrained('addresses')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
        Schema::dropIfExists('medical_data');
        Schema::dropIfExists('contact_info');
        Schema::dropIfExists('addresses');
    }
};
