<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected array $relatedTables = [
        'medical_data', 
        'contact_info', 
        'addresses', 
        'appointments'
    ];

    public function up(): void
    {
        // 1. Eliminamos las restricciones de clave foránea
        foreach ($this->relatedTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['patient_id']);
            });
        }

        // 2. Eliminamos las columnas enteras antiguas (ATENCIÓN: Esto vaciará los datos de estas columnas)
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn('id');
        });
        
        foreach ($this->relatedTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('patient_id');
            });
        }

        // 3. Creamos las nuevas columnas de tipo UUID nativo desde cero y restauramos relaciones
        Schema::table('patients', function (Blueprint $table) {
            $table->uuid('id')->primary()->first(); 
        });
        
        foreach ($this->relatedTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->uuid('patient_id'); 
                $table->foreign('patient_id')
                      ->references('id')->on('patients')
                      ->onDelete('cascade')
                      ->onUpdate('cascade');
            });
        }
    }

    public function down(): void
    {
        // 1. Revertimos eliminando las foráneas
        foreach ($this->relatedTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['patient_id']);
            });
        }

        // 2. Eliminamos las columnas UUID
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn('id');
        });
        
        foreach ($this->relatedTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('patient_id');
            });
        }

        // 3. Restauramos los enteros autoincrementables originales
        Schema::table('patients', function (Blueprint $table) {
            $table->increments('id')->first();
        });
        
        foreach ($this->relatedTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedInteger('patient_id');
                $table->foreign('patient_id')
                      ->references('id')->on('patients')
                      ->onDelete('cascade')
                      ->onUpdate('cascade');
            });
        }
    }
};