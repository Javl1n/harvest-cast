<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commodity extends Model
{
    protected $fillable = ['name'];

    public function variants() {
        return $this->hasMany(CommodityVariant::class, "commodity_id");
    }
}
