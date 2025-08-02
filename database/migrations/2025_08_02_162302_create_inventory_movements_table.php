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
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_item_id');
            $table->unsignedBigInteger('from_location_id')->nullable();
            $table->unsignedBigInteger('to_location_id')->nullable();
            $table->string('type'); // in, out, transfer, adjustment, wastage, production
            $table->decimal('quantity', 10, 3);
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->string('reference_type')->nullable(); // invoice, purchase, audit, etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('batch_number')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->timestamp('movement_date');
            $table->timestamps();
            
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items');
            $table->foreign('from_location_id')->references('id')->on('locations');
            $table->foreign('to_location_id')->references('id')->on('locations');
            $table->foreign('user_id')->references('id')->on('users');
            $table->index(['inventory_item_id', 'movement_date']);
            $table->index(['type', 'reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
