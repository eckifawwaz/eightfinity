<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('payment_expires_at')->nullable()->after('midtrans_status');
            $table->index(['booking_date', 'status'], 'bookings_date_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_date_status_index');
            $table->dropColumn('payment_expires_at');
        });
    }
};
