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
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // low_stock, critical_stock, overdue_payment, birthday_reminder, etc.
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->string('title');
            $table->text('message');
            $table->string('reference_type')->nullable(); // inventory_item, customer, invoice, etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->enum('status', ['active', 'resolved', 'dismissed'])->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['type', 'status']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['status', 'priority', 'created_at']);
            $table->index('created_by');
            $table->index('resolved_by');

            // Foreign keys
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};