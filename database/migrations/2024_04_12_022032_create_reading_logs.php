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
        Schema::create('reading_logs', function (Blueprint $table) {
            $table->id();
            $table->string('serial_no');
            $table->string('plant_id');
            $table->float('light_intensity');
            $table->float('temperature');
            $table->float('humidity');
            $table->float('soil_moisture');
            $table->float('soil_fertility');
            $table->float('soil_ph');
            $table->float('nitrogen');
            $table->float('phosphorus');
            $table->float('potassium');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reading_logs');
    }
};
