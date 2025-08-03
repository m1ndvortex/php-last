<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value');
            $table->string('type')->default('string'); // string, number, boolean, json, file
            $table->string('category')->default('general'); // general, tax, communication, theme
            $table->text('description')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();
            
            $table->index(['category', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_configurations');
    }
};