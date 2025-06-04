<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
    use HasFactory;

   protected $fillable = [
    'category_id',
    'ar_title',
    'en_title',
    'image',
    'en_location',
    'ar_location',
];


    // Relationship to HotelRoom model
    public function rooms()
    {
        return $this->hasMany(HotelRoom::class);
    }

    // Relationship to HotelReservation model
    public function reservations()
    {
        return $this->hasMany(HotelReservation::class);
    }
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

}
