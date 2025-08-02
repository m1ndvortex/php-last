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
        Schema::create('stock_audit_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_audit_id');
            $table->unsignedBigInteger('inventory_item_id');
            $table->decimal('system_quantity', 10, 3); // Quantity according to system
            $table->decimal('physical_quantity', 10, 3)->nullable(); // Actual counted quantity
            $table->decimal('variance', 10, 3)->default(0); // Difference
            $table->decimal('unit_cost', 10, 2);
            $table->decimal('variance_value', 10, 2)->default(0); // Financial impact
            $table->text('notes')->nullable();
            $table->boolean('is_counted')->default(false);
            $table->timestamp('counted_at')->nullable();
            $table->timestamps();
            
            $table->foreign('stock_audit_id')->references('id')->on('stock_audits')->onDelete('cascade');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items');
            $table->unique(['stock_audit_id', 'inventory_item_id']);
            $table->index(['is_counted', 'variance']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_audit_items');
    }
};
