<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventHall extends Model
{
    use HasFactory;

    protected $fillable = [
    'category_id',
    'ar_title',
    'en_title',
    'image',
    'en_location',
    'ar_location',
    'capicity',
    'price',
];


    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function ratings()
    {
        return $this->morphMany(Rating::class, 'rateable');
    }



public function favorites()
{
    return $this->morphMany(Favorite::class, 'favoritable');
}
public function eventHallReservations()
{
    return $this->hasMany(EventHallReservation::class);
}
}
