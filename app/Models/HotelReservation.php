<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HotelReservation extends Model
{
    use HasFactory;

 protected $fillable = [
     'user_id',
    'hotel_id',
    'hotel_room_id',
    'start_date',
    'nights',
    'payment_method',
    'price',          // <= from hotel_rooms
    'final_price',    // <= total after coupon
    'coupons_id',
    'status',
];


    // Cast the start date to a Carbon instance (datetime)
    protected $casts = [
        'start_date' => 'datetime',
    ];

    // Relationship to HotelRoom model
  public function room()
{
    return $this->belongsTo(HotelRoom::class, 'hotel_room_id');
}

    // Relationship to Hotel model
    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    // Relationship to User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Method to calculate the check-out date based on start date and stay duration
    public function getCheckOutDateAttribute()
    {
        return $this->start_date->copy()->addDays($this->stay_duration);
}
}