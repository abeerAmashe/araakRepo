<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;
    protected $table = 'branchs';
    protected $fillable = [
        'address',
        'latitude',
        'longitude'
    ];

    public function room()
    {
        return $this->hasMany(Room::class);
    }

    public function subManager()
    {
        return $this->belongsTo(SubManager::class);
    }

    public function galleryManager()
    {
        return $this->belongsTo(GallaryManager::class);
    }
}
