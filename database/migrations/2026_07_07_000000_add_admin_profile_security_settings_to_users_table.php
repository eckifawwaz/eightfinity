<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('timezone')->default('Asia/Jakarta')->after('address');
            $table->string('language')->default('en')->after('timezone');
            $table->boolean('two_factor_enabled')->default(false)->after('language');
            $table->string('two_factor_code_hash')->nullable()->after('two_factor_enabled');
            $table->timestamp('two_factor_expires_at')->nullable()->after('two_factor_code_hash');
            $table->json('notification_preferences')->nullable()->after('two_factor_expires_at');
            $table->timestamp('password_changed_at')->nullable()->after('notification_preferences');
            $table->timestamp('last_login_at')->nullable()->after('password_changed_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'timezone',
                'language',
                'two_factor_enabled',
                'two_factor_code_hash',
                'two_factor_expires_at',
                'notification_preferences',
                'password_changed_at',
                'last_login_at',
            ]);
        });
    }
};
