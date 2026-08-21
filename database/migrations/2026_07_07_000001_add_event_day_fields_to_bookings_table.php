<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedInteger('photos_taken')->default(0)->after('status');
            $table->boolean('booth_paused')->default(false)->after('photos_taken');
            $table->json('equipment_status')->nullable()->after('booth_paused');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['photos_taken', 'booth_paused', 'equipment_status']);
        });
    }
};
