<?php

namespace App\Models;

use App\Models\Type as ModelsType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Mockery\Matcher\Type;

class Wood extends Model
{
    use HasFactory;

    protected $table = 'woods';

    protected $fillable = [
        'id',
        'name',
        'price_per_meter',
        'room_detail_id'

    ];


    public function rooms()
    {
        return $this->belongsToMany(Room::class, 'room_wood');
    }

    public function colors()
    {
        return $this->hasMany(Color::class);
    }

    public function types()
    {
        return $this->hasMany(ModelsType::class);
    }
    public function itemDetails()
    {
        return $this->belongsToMany(ItemDetail::class);
    }
    public function roomDetail()
    {
        return $this->belongsTo(RoomDetail::class);
    }

    public function items()
    {
        return $this->belongsToMany(Item::class, 'item_wood');
    }
}
