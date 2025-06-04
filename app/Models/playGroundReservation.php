<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayGroundReservation extends Model
{
    protected $fillable = [
        'user_id',
        'play_ground_id',
        'reservation_date',
        'reservation_time',
        'payment_method',
        'price',            // السعر الأساسي
        'final_price',      // السعر بعد الخصم
        'coupon_id',        // معرف الكوبون المستخدم
        'discount_applied'  // هل تم تطبيق الخصم؟
    ];

// في نموذج PlayGroundReservation
public function user()
{
    return $this->belongsTo(User::class);
}

public function playGround()
{
    return $this->belongsTo(PlayGround::class);
}

public function coupons()
{
    return $this->belongsTo(Coupons::class, 'coupons_id');
}
}
