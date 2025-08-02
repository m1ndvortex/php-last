<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->string('description');
            $table->string('description_persian')->nullable();
            $table->date('transaction_date');
            $table->enum('type', ['journal', 'invoice', 'payment', 'adjustment', 'recurring']);
            $table->string('source_type')->nullable(); // invoice, payment, etc.
            $table->unsignedBigInteger('source_id')->nullable();
            $table->decimal('total_amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->decimal('exchange_rate', 15, 6)->default(1);
            $table->boolean('is_locked')->default(0);
            $table->boolean('is_recurring')->default(0);
            $table->unsignedBigInteger('recurring_template_id')->nullable();
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->json('tags')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users');
            $table->index(['transaction_date', 'type']);
            $table->index(['source_type', 'source_id']);
            $table->index(['is_locked']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};