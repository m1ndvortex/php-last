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
        Schema::create('bill_of_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('finished_item_id'); // The final product
            $table->unsignedBigInteger('component_item_id'); // The raw material/component
            $table->decimal('quantity_required', 10, 3); // How much of component is needed
            $table->decimal('wastage_percentage', 5, 2)->default(0); // Expected wastage %
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('finished_item_id')->references('id')->on('inventory_items');
            $table->foreign('component_item_id')->references('id')->on('inventory_items');
            $table->unique(['finished_item_id', 'component_item_id']);
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_of_materials');
    }
};
