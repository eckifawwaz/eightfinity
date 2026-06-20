<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('booking_code')->unique();
            $table->string('package_slug');
            $table->string('package_name');
            $table->unsignedTinyInteger('package_option')->default(0);
            $table->date('booking_date');
            $table->string('booking_time', 5);
            $table->unsignedInteger('people');
            $table->unsignedBigInteger('amount');
            $table->string('payment_method');
            $table->string('payment_provider');
            $table->string('payment_proof')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
