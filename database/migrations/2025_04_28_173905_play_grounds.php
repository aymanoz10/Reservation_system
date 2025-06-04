<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Category;
use App\Models\ReservationTime;
use ReservationTime as GlobalReservationTime;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('play_grounds', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Category::class)->constrained();
            $table->enum('sport', ['Football', 'Basketball', 'Tennis' ,]);
            $table->string('ar_title');
            $table->string('en_title');
            $table->string('image');
            $table->string('en_location');
            $table->string('ar_location');
            $table->integer('price');
            $table->integer('capicity');
            $table->boolean('is_closed')->nullable();
            $table->dateTime('closed_from')->nullable();
            $table->dateTime('closed_until')->nullable();
            $table->timestamps();
        });     }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
