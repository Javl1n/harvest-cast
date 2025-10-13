<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CropRecommendation extends Model
{
    protected $fillable = [
        'commodity_id',
        'moisture_min',
        'moisture_max',
        'temperature_min',
        'temperature_max',
        'seasons',
        'planting_months',
        'favorable_weather',
        'unfavorable_weather',
        'planting_tips',
        'harvest_time',
        'harvest_days',
        'optimal_conditions',
        'water_requirements',
        'is_active'
    ];

    protected $casts = [
        'seasons' => 'array',
        'planting_months' => 'array',
        'favorable_weather' => 'array',
        'unfavorable_weather' => 'array',
        'is_active' => 'boolean'
    ];

    /**
     * Get the commodity that this recommendation belongs to
     */
    public function commodity()
    {
        return $this->belongsTo(Commodity::class);
    }

    /**
     * Scope for active recommendations
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get moisture range as array
     */
    public function getMoistureRangeAttribute()
    {
        return [
            'min' => $this->moisture_min,
            'max' => $this->moisture_max
        ];
    }

    /**
     * Get temperature range as array
     */
    public function getTemperatureRangeAttribute()
    {
        return [
            'min' => $this->temperature_min,
            'max' => $this->temperature_max
        ];
    }
}
