<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::create('restaurant_reservations', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->foreignId('restaurant_id')->constrained()->onDelete('cascade');
        $table->dateTime('reservation_time');
        $table->enum('area_type', ['indoor_hall', 'outdoor_terrace'])->nullable();
        $table->integer('guests');
$table->enum('status', ['confirmed', 'cancelled', 'missed' , 'rejected'])->default('confirmed');
        $table->timestamps();
    });
}

    public function down(): void
    {
        Schema::dropIfExists('restaurants_reservations');
    }
};
