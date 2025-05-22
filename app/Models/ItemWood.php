<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemWood extends Model
{
    use HasFactory;

    protected $table = 'item_wood';

    public $timestamps = false; // لأن المايجريشن ما حاط timestamps

    protected $fillable = [
        'wood_id',
        'item_detail_id',
        
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function wood()
    {
        return $this->hasMany(Wood::class);
    }
}