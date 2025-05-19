<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customization extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'item_id',
        'wood_id',
        'fabric_id',
        'extra_length',
        'extra_width',
        'extra_height',
        'old_price',
        'final_price',
        'wood_color',
        'fabric_color',
        'customer_id',
        'final_price'
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function wood()
    {
        return $this->belongsTo(Wood::class);
    }

    public function fabric()
    {
        return $this->belongsTo(Fabric::class);
    }
}