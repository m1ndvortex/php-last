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
        Schema::table('customers', function (Blueprint $table) {
            $table->date('last_purchase_date')->nullable()->after('anniversary');
            $table->decimal('total_purchases', 12, 2)->default(0)->after('last_purchase_date');
            $table->integer('purchase_count')->default(0)->after('total_purchases');
            $table->decimal('outstanding_balance', 12, 2)->default(0)->after('purchase_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['last_purchase_date', 'total_purchases', 'purchase_count', 'outstanding_balance']);
        });
    }
};