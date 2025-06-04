<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    use HasFactory;
    protected $fillable = [
    'category_id',
    'ar_title',
    'en_title',
    'image',
    'capacity',
    'ar_location',
    'en_location',
];




    public function ratings()
{
    return $this->morphMany(Rating::class, 'rateable');
}


public function reservations()
{
    return $this->hasMany(RestaurantReservation::class);
}
}
