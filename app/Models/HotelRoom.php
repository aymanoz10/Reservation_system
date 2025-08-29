<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelRoom extends Model
{
    use HasFactory;

   protected $fillable = [
    'hotel_id',
    'floor',
    'room_number',
    'type',
    'image',
    'capacity',
    'price_per_night',
    'description',
];


    // Relationship to Hotel model
    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function HotelReservations()
    {
        return $this->hasMany(HotelReservation::class);
    }
}
