<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('event_hall_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('event_hall_id')->constrained('event_halls')->cascadeOnDelete(); $table->foreignId('coupons_id')->nullable()->constrained('coupons')->cascadeOnDelete();
            $table->enum('event_type', ['wedding', 'funeral']);
            $table->date('reservation_date');
            $table->string('reservation_time');
            $table->integer('guests');
            $table->decimal('price', 10, 2);
            $table->decimal('final_price', 8, 2)->nullable();
            $table->boolean('discount_applied')->default(false);
            $table->enum('payment_method', ['cash', 'credit_card', 'MTN_CASH'])->default('cash');
            $table->enum('status', ['confirmed', 'cancelled', 'done', 'rejected', 'missed'])->default('confirmed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('event_hall_reservations');
    }
};
