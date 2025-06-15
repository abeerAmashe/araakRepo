<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemDetail extends Model
{
    use HasFactory;
    protected $fillable = [
        'item_id',
        'wood_id',
        'fabric_id',
        'wood_length',
        'wood_width',
        'wood_height',
        'fabric_dimension',   

    ];

    public function fabric()
    {
        return $this->belongsTo(Fabric::class);
    }

    public function wood()
    {
        return $this->belongsTo(Wood::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
    public function itemWoods()
    {
        return $this->HasMany(ItemWood::class);
    }
     public function itemFabrics()
    {
        return $this->HasMany(ItemFabric::class);
    }
}