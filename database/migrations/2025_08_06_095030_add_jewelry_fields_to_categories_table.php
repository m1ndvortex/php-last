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
        Schema::table('categories', function (Blueprint $table) {
            $table->decimal('default_gold_purity', 5, 3)->nullable()->after('parent_id');
            $table->string('image_path')->nullable()->after('default_gold_purity');
            $table->integer('sort_order')->default(0)->after('image_path');
            $table->json('specifications')->nullable()->after('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['default_gold_purity', 'image_path', 'sort_order', 'specifications']);
        });
    }
};