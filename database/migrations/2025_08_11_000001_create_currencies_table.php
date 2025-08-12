<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Check if currencies table already exists
        if (!Schema::hasTable('currencies')) {
            Schema::create('currencies', function (Blueprint $table) {
                $table->id();
                $table->string('code', 3)->unique();
                $table->string('name');
                $table->string('name_persian')->nullable();
                $table->string('symbol', 10);
                $table->decimal('exchange_rate', 10, 6)->default(1);
                $table->boolean('is_base_currency')->default(false);
                $table->boolean('is_active')->default(true);
                $table->integer('decimal_places')->default(2);
                $table->timestamps();

                $table->index(['is_active']);
                $table->index(['is_base_currency']);
            });
        } else {
            // Add missing columns to existing currencies table
            Schema::table('currencies', function (Blueprint $table) {
                if (!Schema::hasColumn('currencies', 'name_persian')) {
                    $table->string('name_persian')->nullable()->after('name');
                }
                if (!Schema::hasColumn('currencies', 'is_base_currency')) {
                    $table->boolean('is_base_currency')->default(false)->after('exchange_rate');
                }
                if (!Schema::hasColumn('currencies', 'decimal_places')) {
                    $table->integer('decimal_places')->default(2)->after('is_active');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};