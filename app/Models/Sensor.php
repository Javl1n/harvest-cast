<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sensor extends Model
{
    /** @use HasFactory<\Database\Factories\SensorFactory> */
    use HasFactory, HasUuids;

    protected $fillable = ["mac_address"];

    public function readings()
    {
        return $this->hasMany(SensorReading::class, "sensor_id");
    }

    public function latestReading()
    {
        return $this->hasOne(SensorReading::class, 'sensor_id')->latestOfMany();
    }

    public function oldestReading()
    {
        return $this->hasOne(SensorReading::class, 'sensor_id')->oldestOfMany();
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'sensor_id');
    }

    public function latestSchedule()
    {
        return $this->hasOne(Schedule::class, 'sensor_id')->latestOfMany();
    }
}