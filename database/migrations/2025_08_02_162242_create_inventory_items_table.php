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
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_persian')->nullable();
            $table->text('description')->nullable();
            $table->text('description_persian')->nullable();
            $table->string('sku')->unique();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('location_id');
            $table->decimal('quantity', 10, 3)->default(0);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('cost_price', 10, 2);
            $table->decimal('gold_purity', 5, 3)->nullable(); // For gold items (e.g., 18.000)
            $table->decimal('weight', 8, 3)->nullable(); // Weight in grams
            $table->string('serial_number')->nullable()->unique();
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('minimum_stock', 10, 3)->default(0);
            $table->decimal('maximum_stock', 10, 3)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('track_serial')->default(false);
            $table->boolean('track_batch')->default(false);
            $table->json('metadata')->nullable(); // Additional item-specific data
            $table->timestamps();
            
            $table->foreign('category_id')->references('id')->on('categories');
            $table->foreign('location_id')->references('id')->on('locations');
            $table->index(['is_active', 'category_id', 'location_id']);
            $table->index(['batch_number', 'expiry_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
