<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;



class Coupons extends Model
{
    use HasFactory;

 protected $fillable = [
    'code',
    'discount_percentage',
    'usage_limit',
    'used_count',
    'expires_at',
    'is_active',
];


    // علاقة بين DiscountCode و User (عن طريق reservation_logs)
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    // علاقات مع أنواع الحجوزات المختلفة

    public function eventHallReservations()
    {
        return $this->hasMany(EventHallReservation::class, 'Coupons_id');
    }

    public function hotelReservations()
    {
        return $this->hasMany(HotelReservation::class, 'Coupons_id');
    }

    public function playgroundReservations()
    {
        return $this->hasMany(PlayGroundReservation::class, 'Coupons_id');
    }

    public function restaurantReservations()
    {
        return $this->hasMany(RestaurantReservation::class, 'Coupons_id');
    }

    public function tourReservations()
    {
        return $this->hasMany(ToursReservation::class, 'Coupons_id');
    }

    // تحقق من صلاحية الكود
    public function isValid()
    {
        return $this->is_active
            && ($this->expires_at === null || $this->expires_at > now())
            && ($this->usage_limit === null || $this->used_count < $this->usage_limit);
    }
}
