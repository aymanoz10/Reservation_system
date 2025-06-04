<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    protected $fillable = [
    'ar_title',
    'en_title',
    'image',
];


    public function hotels()
    {
        return $this->hasMany(Hotel::class);
    }

    public function restaurants()
    {
        return $this->hasMany(Restaurant::class);
    }

    public function eventHalls()
    {
        return $this->hasMany(EventHall::class);
    }

    public function playGrounds()
    {
        return $this->hasMany(PlayGround::class);
    }

    public function tours()
    {
        return $this->hasMany(Tour::class);
    }
}
