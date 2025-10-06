<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SensorReading extends Model
{
    /** @use HasFactory<\Database\Factories\SensorReadingFactory> */
    use HasFactory;

    protected $fillable = ["longitude", "latitude", "moisture"];

    public function sensor()
    {
        return $this->belongsTo(Sensor::class, "sensor_id");
    }
}
