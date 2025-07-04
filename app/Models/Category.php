<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'name'
    ];

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
}
