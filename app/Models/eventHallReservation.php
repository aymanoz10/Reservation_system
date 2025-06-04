<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventHallReservation extends Model
{
    use HasFactory;
protected $fillable = [
    'user_id',
    'event_type',
    'reservation_date',
    'reservation_time',
    'event_hall_id',
    'guests',
    'price',
    'final_price',      // إضافة السعر النهائي بعد الخصم
    'discount_applied', // إضافة حالة تطبيق الخصم
    'payment_method',   // إضافة وسيلة الدفع
    'status',           // إضافة حالة الحجز
    'coupons_id',       // تسجيل الكوبون المستخدم
];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
  // App\Models\EventHallReservation.php

public function eventHall()
{
    return $this->belongsTo(EventHall::class, 'event_hall_id');
}


}
