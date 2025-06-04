<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestaurantReservation extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'restaurant_id',
        'restaurant_table_id',
        'reservation_time',
        'guests',
        'status',
        'area_type',
        'payment_method',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $casts = [
        'reservation_time' => 'datetime',
    ];
    
    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

 
}
