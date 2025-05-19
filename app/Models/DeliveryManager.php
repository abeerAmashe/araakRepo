<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryManager extends Model
{
    use HasFactory;

    protected $fillable = [
        'id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function placeCost()
    {
        return $this->hasOne(PlaceCost::class);
    }
}
