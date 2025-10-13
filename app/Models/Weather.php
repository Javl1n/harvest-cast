<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Weather extends Model
{
    protected $fillable = ['info'];
    
    protected $casts = [
        'info' => 'array'
    ];
    
    /**
     * Get the decoded weather info
     */
    protected function decodedInfo(): Attribute
    {
        return Attribute::make(
            get: fn () => is_string($this->info) ? json_decode($this->info, true) : $this->info,
        );
    }
    
    /**
     * Get the temperature from weather info
     */
    protected function temperature(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->decoded_info['main']['temp'] ?? null,
        );
    }
    
    /**
     * Get the humidity from weather info
     */
    protected function humidity(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->decoded_info['main']['humidity'] ?? null,
        );
    }
    
    /**
     * Get the weather condition from weather info
     */
    protected function condition(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->decoded_info['weather'][0]['main'] ?? null,
        );
    }
    
    /**
     * Get the latest weather record
     */
    public static function latest()
    {
        return static::query()->latest();
    }
}
