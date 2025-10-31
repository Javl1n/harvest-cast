<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    protected $fillable = ['variant_id', 'price', 'date'];

    public function variant()
    {
        return $this->belongsTo(CommodityVariant::class, 'variant_id');
    }
}
