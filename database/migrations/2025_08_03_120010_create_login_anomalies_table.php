<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_anomalies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // suspicious_ip, new_device, rapid_attempts, geo_anomaly, time_anomaly
            $table->string('severity'); // low, medium, high, critical
            $table->string('ip_address');
            $table->text('user_agent');
            $table->string('location')->nullable();
            $table->json('detection_data'); // Additional data about the anomaly
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->boolean('is_false_positive')->default(false);
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index(['type', 'severity']);
            $table->index(['is_resolved', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_anomalies');
    }
};