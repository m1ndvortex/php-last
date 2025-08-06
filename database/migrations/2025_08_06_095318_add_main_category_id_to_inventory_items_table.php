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
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->unsignedBigInteger('main_category_id')->nullable()->after('category_id');
            $table->foreign('main_category_id')->references('id')->on('categories');
            $table->index(['main_category_id', 'category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropForeign(['main_category_id']);
            $table->dropIndex(['main_category_id', 'category_id']);
            $table->dropColumn('main_category_id');
        });
    }
};