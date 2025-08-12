<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_entries', function (Blueprint $table) {
            $table->decimal('original_debit_amount', 15, 2)->nullable()->after('debit_amount');
            $table->decimal('original_credit_amount', 15, 2)->nullable()->after('credit_amount');
            $table->string('currency', 3)->default('USD')->after('original_credit_amount');
            $table->decimal('exchange_rate', 10, 6)->default(1)->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('transaction_entries', function (Blueprint $table) {
            $table->dropColumn(['original_debit_amount', 'original_credit_amount', 'currency', 'exchange_rate']);
        });
    }
};