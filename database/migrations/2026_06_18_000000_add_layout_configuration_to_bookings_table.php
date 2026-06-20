<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('layout_name')->default('Wedding Setup')->after('booking_location');
            $table->string('booth_size')->default('3 x 3 meter')->after('layout_name');
            $table->string('printer_position')->default('inside')->after('booth_size');
            $table->string('entrance_direction')->default('left')->after('printer_position');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'layout_name',
                'booth_size',
                'printer_position',
                'entrance_direction',
            ]);
        });
    }
};
