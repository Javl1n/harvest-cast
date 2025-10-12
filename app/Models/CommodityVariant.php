<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommodityVariant extends Model
{
    protected $fillable = ["name"];

    public function commodity()
    {
        return $this->belongsTo(Commodity::class, 'commodity_id');
    }

    public function prices()
    {
        return $this->hasMany(Price::class, "variant_id");
    }
}
