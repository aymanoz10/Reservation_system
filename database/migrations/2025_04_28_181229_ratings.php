<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;


return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->morphs('rateable'); 
            $table->tinyInteger('rating'); 
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->foreignIdFor(User::class)->constrained();
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
