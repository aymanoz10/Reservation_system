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
        Schema::create('tours_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('tour_id')->constrained()->onDelete('cascade');
            $table->foreignId('coupons_id')->nullable()->constrained('coupons')->cascadeOnDelete();
            $table->integer('guests');
            $table->decimal('price', 10, 2);
            $table->string('payment_method'); // 'cash', 'paypal', 'credit_card'
            $table->decimal('final_price', 8, 2)->nullable();
            $table->enum('status', ['confirmed', 'rejected', 'cancelled', 'done'])->default('confirmed');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('discount_applied')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tours_resrvations');
    }
};
