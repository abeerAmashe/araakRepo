<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkshopManagerRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'item_id',           
        'room_id', 
        'customization_id',
        'room_customization_id',                
        'required_count',   
        'branch_id'
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
}