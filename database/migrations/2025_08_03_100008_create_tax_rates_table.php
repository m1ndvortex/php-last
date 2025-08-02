<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_persian')->nullable();
            $table->decimal('rate', 5, 4); // e.g., 0.0825 for 8.25%
            $table->enum('type', ['sales', 'purchase', 'vat', 'income', 'other']);
            $table->boolean('is_compound')->default(false);
            $table->boolean('is_active')->default(true);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index(['effective_from', 'effective_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};