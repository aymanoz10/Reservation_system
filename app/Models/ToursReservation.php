<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ToursReservation extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',  // <-- Add this line
        'tour_id',
        'guests',
        'price',
        'status',
        'payment_method',
        'start_date',
        'end_date',
    ];

    public function user()
{
    return $this->belongsTo(User::class);
}

public function tour()
{
    return $this->belongsTo(Tour::class);
}

}
