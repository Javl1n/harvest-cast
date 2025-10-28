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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commodity_id')->constrained('commodities', 'id');
            $table->foreignId('sensor_id')->constrained('sensors', 'id');
            $table->float('hectares', 2);
            $table->integer('seeds_planted');
            $table->date('date_planted');
            $table->date('expected_harvest_date');
            $table->date('actual_harvest_date')->nullable();
            $table->float('yield', 2)->nullable();
            $table->float('expected_yield', 2)->nullable();
            $table->float('expected_income', 2)->nullable();
            $table->float('income', 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
