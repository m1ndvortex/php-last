<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('two_factor_type')->nullable(); // sms, totp
            $table->string('two_factor_phone')->nullable(); // for SMS 2FA
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->text('two_factor_backup_codes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_type',
                'two_factor_phone',
                'two_factor_confirmed_at',
                'two_factor_backup_codes'
            ]);
        });
    }
};