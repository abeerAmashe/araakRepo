<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomDetail extends Model
{
    use HasFactory;
    protected $fillable = [
        'room_id',
        'count'
    ];


    public function woods()
    {
        return $this->hasMany(Wood::class); 
    }



    public function fabrics()
    {
        return $this->hasMany(Fabric::class);
    }
}