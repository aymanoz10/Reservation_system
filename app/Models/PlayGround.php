<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ReservationTime;

class PlayGround extends Model
{
      protected $fillable = [
        'category_id',
        'sport',
        'ar_title',
        'en_title',
        'image',
        'en_location',
        'ar_location',
        'price',
        'capicity',  // (note: probably means 'capacity', but keep it as is if in DB)
        'is_closed',
        'closed_from',
        'closed_until',
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

 

}
