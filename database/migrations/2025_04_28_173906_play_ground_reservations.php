<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\PlayGround;
use App\Models\Coupon;
use App\Models\Coupons;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('play_ground_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(PlayGround::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Coupons::class)->nullable()->constrained()->cascadeOnDelete(); // تسجيل الكوبون المستخدم
            $table->date('reservation_date');
            $table->string('reservation_time');
            $table->enum('payment_method', ['cash', 'credit_card', 'MTN_CASH'])->default('cash');
            $table->decimal('price', 8, 2); // السعر الأساسي قبل الخصم
            $table->decimal('final_price', 8, 2)->nullable(); // السعر النهائي بعد الخصم
            $table->enum('status', ['confirmed', 'cancelled', 'done', 'rejected', 'missed'])->default('confirmed');
            $table->boolean('discount_applied')->default(false); // هل تم تطبيق الخصم؟
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('play_ground_reservations');
    }
};
