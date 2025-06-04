<?php

use App\Models\Hotel;
use App\Models\HotelRoom;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Coupons;

return new class extends Migration
{
 public function up(): void
{
    Schema::create('hotel_reservations', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->foreignId('hotel_id')->constrained()->onDelete('cascade');
        $table->foreignId('hotel_room_id')->constrained()->onDelete('cascade');
        $table->foreignIdFor(Coupons::class)->nullable()->constrained()->cascadeOnDelete(); // تسجيل الكوبون المستخدم
        $table->date('start_date');
        $table->unsignedInteger('nights');
        $table->enum('payment_method', ['cash', 'credit_card', 'paypal'])->nullable();
        $table->decimal('price', 8, 2); // السعر الأساسي قبل الخصم
        $table->decimal('final_price', 8, 2)->nullable(); // السعر النهائي بعد الخصم
        $table->enum('status', ['confirmed', 'cancelled', 'done', 'rejected', 'missed'])->default('confirmed');
        $table->timestamps();
    });
}




    public function down()
    {
        Schema::dropIfExists('hotel_reservations');
    }
};
