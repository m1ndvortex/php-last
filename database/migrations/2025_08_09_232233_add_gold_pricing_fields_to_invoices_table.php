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
        Schema::table('invoices', function (Blueprint $table) {
            // Add gold pricing fields
            $table->decimal('gold_price_per_gram', 10, 2)->nullable()->after('total_amount');
            $table->decimal('labor_percentage', 5, 2)->nullable()->after('gold_price_per_gram');
            $table->decimal('profit_percentage', 5, 2)->nullable()->after('labor_percentage');
            $table->decimal('tax_percentage', 5, 2)->nullable()->after('profit_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'gold_price_per_gram',
                'labor_percentage',
                'profit_percentage',
                'tax_percentage'
            ]);
        });
    }
};