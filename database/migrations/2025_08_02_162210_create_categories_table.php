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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_persian')->nullable();
            $table->text('description')->nullable();
            $table->text('description_persian')->nullable();
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->timestamps();
            
            $table->foreign('parent_id')->references('id')->on('categories')->onDelete('set null');
            $table->index(['is_active', 'parent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
