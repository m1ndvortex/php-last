<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_export_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // full_export, partial_export, customer_data, etc.
            $table->string('format'); // json, csv, pdf
            $table->json('data_types'); // Array of data types to export
            $table->json('filters')->nullable(); // Export filters
            $table->string('status'); // pending, processing, completed, failed
            $table->string('file_path')->nullable(); // Path to generated file
            $table->integer('file_size')->nullable(); // File size in bytes
            $table->timestamp('expires_at')->nullable(); // When the file expires
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_export_requests');
    }
};