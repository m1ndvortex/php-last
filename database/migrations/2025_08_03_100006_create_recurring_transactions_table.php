<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_persian')->nullable();
            $table->string('description');
            $table->string('description_persian')->nullable();
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly']);
            $table->integer('interval')->default(1); // every X frequency
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_run_date');
            $table->integer('max_occurrences')->nullable();
            $table->integer('occurrences_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('transaction_template'); // stores the transaction structure
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->index(['next_run_date', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_transactions');
    }
};