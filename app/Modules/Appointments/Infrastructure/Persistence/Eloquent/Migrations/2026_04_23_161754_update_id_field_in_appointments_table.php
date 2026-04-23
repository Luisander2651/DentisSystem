<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Update the 'id' field to be a uuid instead of an auto-incrementing integer
            $table->dropColumn('id');
            $table->uuid('id')->primary()->first(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Revert the 'id' field back to an auto-incrementing integer
            $table->dropColumn('id');
            $table->id('id')->primary()->first();
        });
    }
};
