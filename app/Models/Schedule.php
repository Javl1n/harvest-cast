<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'commodity_id',
        'sensor_id',
        'hectares',
        'seeds_planted',
        'date_planted',
        'expected_harvest_date',
        'actual_harvest_date',
        'yield',
        'expected_income',
        'income'
    ];

    public function sensor()
    {
        return $this->belongsTo(Sensor::class, 'sensor_id');
    }

    public function commodity()
    {
        return $this->belongsTo(Commodity::class, 'commodity_id');
    }

    
}
