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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_persian')->nullable();
            $table->text('description')->nullable();
            $table->text('description_persian')->nullable();
            $table->string('code')->unique();
            $table->string('type')->default('storage'); // storage, showcase, safe, exhibition
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable(); // Additional location-specific data
            $table->timestamps();
            
            $table->index(['is_active', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
