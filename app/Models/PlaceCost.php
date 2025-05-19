<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlaceCost extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'place',
        'price'
    ];

    public function deliveryManager()
    {
        return $this->belongsTo(DeliveryManager::class);
    }
}
