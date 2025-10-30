<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'commodity_id',
        'sensor_id',
        'acres',
        'seed_weight_kg',
        'date_planted',
        'expected_harvest_date',
        'actual_harvest_date',
        'yield',
        'expected_yield',
        'expected_income',
        'income',
    ];

    protected function casts(): array
    {
        return [
            'seed_weight_kg' => 'decimal:2',
            'date_planted' => 'datetime',
            'expected_harvest_date' => 'datetime',
            'actual_harvest_date' => 'datetime',
        ];
    }

    public function sensor()
    {
        return $this->belongsTo(Sensor::class, 'sensor_id');
    }

    public function commodity()
    {
        return $this->belongsTo(Commodity::class, 'commodity_id');
    }
}
