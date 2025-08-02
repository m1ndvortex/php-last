<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_audits', function (Blueprint $table) {
            $table->id();
            $table->string('audit_number')->unique();
            $table->unsignedBigInteger('location_id')->nullable(); // null for all locations
            $table->string('status')->default('pending'); // pending, in_progress, completed, cancelled
            $table->date('audit_date');
            $table->unsignedBigInteger('auditor_id');
            $table->text('notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->foreign('location_id')->references('id')->on('locations');
            $table->foreign('auditor_id')->references('id')->on('users');
            $table->index(['status', 'audit_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_audits');
    }
};
