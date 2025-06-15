<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemFabric extends Model
{
    use HasFactory;

    protected $table = 'item_fabric';

    public $timestamps = false; 
    protected $fillable = [
        'fabric_id',
        'item_detail_id'
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function fabric()
    {
        return $this->hasMany(Fabric::class);
    }
}