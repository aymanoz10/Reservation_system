<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Hotel;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::create('hotel_rooms', function (Blueprint $table) {
        $table->id();
        $table->foreignId('hotel_id')->constrained()->onDelete('cascade'); 
        $table->integer('floor'); 
        $table->integer('room_number');
        $table->string('image');
        $table->string('type'); 
        $table->integer('capacity'); 
        $table->decimal('price_per_night', 8, 2); 
        $table->text('description')->nullable(); 
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
