<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            // Add missing columns to existing audit_logs table
            $table->string('url')->nullable()->after('user_agent');
            $table->string('method')->nullable()->after('url'); // GET, POST, PUT, DELETE
            $table->json('request_data')->nullable()->after('method'); // Request parameters
            $table->text('description')->nullable()->after('request_data'); // Human readable description
            $table->string('severity')->default('info')->after('description'); // info, warning, error, critical
            
            // Add missing indexes
            $table->index(['severity', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['severity', 'created_at']);
            $table->dropColumn(['url', 'method', 'request_data', 'description', 'severity']);
        });
    }
};