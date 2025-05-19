<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepairRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'explain',
        'photo'
    ];

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }
    public function report()
    {
        return $this->belongsTo(Report::class);
    }
}