<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TourStop extends Model
{
    use HasFactory;
    protected $fillable = [
    'tour_id',
    'sequence',
    'ar_title',
    'en_title',
    'image',
    'ar_description',
    'en_description',
];


public function tour()
{
    return $this->belongsTo(Tour::class);
}


}
