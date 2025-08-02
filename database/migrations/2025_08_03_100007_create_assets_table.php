<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_number')->unique();
            $table->string('name');
            $table->string('name_persian')->nullable();
            $table->text('description')->nullable();
            $table->enum('category', ['equipment', 'furniture', 'vehicle', 'building', 'software', 'other']);
            $table->decimal('purchase_cost', 15, 2);
            $table->date('purchase_date');
            $table->decimal('salvage_value', 15, 2)->default(0);
            $table->integer('useful_life_years');
            $table->enum('depreciation_method', ['straight_line', 'declining_balance', 'units_of_production']);
            $table->decimal('accumulated_depreciation', 15, 2)->default(0);
            $table->decimal('current_value', 15, 2);
            $table->enum('status', ['active', 'disposed', 'sold', 'damaged'])->default('active');
            $table->date('disposal_date')->nullable();
            $table->decimal('disposal_value', 15, 2)->nullable();
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('cost_center_id')->references('id')->on('cost_centers');
            $table->index(['status']);
            $table->index(['category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};