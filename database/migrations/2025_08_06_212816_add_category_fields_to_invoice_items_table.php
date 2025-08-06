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
            $table->unsignedBigInteger('category_id')->nullable()->after('inventory_item_id');
            $table->unsignedBigInteger('main_category_id')->nullable()->after('category_id');
            $table->string('category_path')->nullable()->after('main_category_id');
            
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('main_category_id')->references('id')->on('categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['main_category_id']);
            $table->dropColumn(['category_id', 'main_category_id', 'category_path']);
        });
    }
};
