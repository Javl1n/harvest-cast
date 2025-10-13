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
        Schema::create('crop_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commodity_id')->constrained('commodities')->onDelete('cascade'); // Reference to commodity
            $table->integer('moisture_min'); // Minimum moisture requirement (%)
            $table->integer('moisture_max'); // Maximum moisture requirement (%)
            $table->integer('temperature_min'); // Minimum temperature (Celsius)
            $table->integer('temperature_max'); // Maximum temperature (Celsius)
            $table->json('seasons'); // Suitable seasons array
            $table->json('planting_months'); // Optimal planting months array
            $table->json('favorable_weather'); // Favorable weather conditions array
            $table->json('unfavorable_weather'); // Unfavorable weather conditions array
            $table->text('planting_tips'); // Planting tips and advice
            $table->string('harvest_time'); // Harvest time range (e.g., "120-150 days")
            $table->integer('harvest_days'); // Average harvest days for calculations
            $table->text('optimal_conditions'); // Optimal growing conditions description
            $table->text('water_requirements'); // Water requirements description
            $table->boolean('is_active')->default(true); // Can be used to enable/disable recommendations
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crop_recommendations');
    }
};
