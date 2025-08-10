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
        Schema::table('invoice_items', function (Blueprint $table) {
            // Add price breakdown fields for dynamic gold pricing
            $table->decimal('base_gold_cost', 15, 2)->default(0)->after('total_price');
            $table->decimal('labor_cost', 15, 2)->default(0)->after('base_gold_cost');
            $table->decimal('profit_amount', 15, 2)->default(0)->after('labor_cost');
            $table->decimal('tax_amount', 15, 2)->default(0)->after('profit_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn([
                'base_gold_cost',
                'labor_cost', 
                'profit_amount',
                'tax_amount'
            ]);
        });
    }
};