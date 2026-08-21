<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('midtrans_order_id')->nullable()->unique()->after('status');
            $table->string('midtrans_snap_token')->nullable()->after('midtrans_order_id');
            $table->text('midtrans_redirect_url')->nullable()->after('midtrans_snap_token');
            $table->string('midtrans_transaction_id')->nullable()->after('midtrans_redirect_url');
            $table->string('midtrans_payment_type')->nullable()->after('midtrans_transaction_id');
            $table->string('midtrans_status')->nullable()->after('midtrans_payment_type');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique(['midtrans_order_id']);
            $table->dropColumn([
                'midtrans_order_id',
                'midtrans_snap_token',
                'midtrans_redirect_url',
                'midtrans_transaction_id',
                'midtrans_payment_type',
                'midtrans_status',
            ]);
        });
    }
};
