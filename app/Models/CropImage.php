<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CropImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'file_path',
        'file_name',
        'ai_analysis',
        'health_status',
        'recommendations',
        'processed',
        'image_date',
    ];

    protected function casts(): array
    {
        return [
            'image_date' => 'date',
            'ai_analysis' => 'json',
            'processed' => 'boolean',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function getImageUrlAttribute(): string
    {
        return asset("storage/{$this->file_path}");
    }
}
