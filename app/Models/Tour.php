<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tour extends Model
{
    use HasFactory;
    protected $fillable = [
    'category_id',
    'ar_title',
    'en_title',
    'ar_description',
    'en_description',
    'image',
    'price',
    'start_date',
    'end_date',
];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];
    
    public function reservations()
    {
        return $this->hasMany(ToursReservation::class);
    }
    
    public function stops()
    {
        return $this->hasMany(TourStop::class)->orderBy('sequence');
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
