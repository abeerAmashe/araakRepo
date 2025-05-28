<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;


    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
    ];


    protected $hidden = [
        'password',
        'remember_token',
    ];


    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];


    public function galleryManager()
    {
        return $this->hasOne(GallaryManager::class);
    }

    public function workshopManager()
    {
        return $this->hasOne(WorkshopManager::class);
    }

    public function deliveryManager()
    {
        return $this->hasOne(deliveryManager::class);
    }

    public function customer()
    {
        return $this->hasOne(Customer::class);
    }

    public function subMamager()
    {
        return $this->hasOne(SubManager::class);
    }


    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function wallets()
{
    return $this->hasMany(Wallet::class);
}


    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}