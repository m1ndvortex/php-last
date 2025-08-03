<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('two_factor_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('code', 6);
            $table->string('type'); // sms, email
            $table->timestamp('expires_at');
            $table->boolean('used')->default(false);
            $table->timestamp('used_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'code', 'type']);
            $table->index(['expires_at', 'used']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('two_factor_codes');
    }
};