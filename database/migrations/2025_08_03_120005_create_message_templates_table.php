<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('type'); // email, sms, whatsapp
            $table->string('category'); // invoice, reminder, birthday, notification
            $table->string('language', 5)->default('en'); // en, fa
            $table->string('subject')->nullable(); // for email templates
            $table->text('content');
            $table->json('variables')->nullable(); // available variables for template
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['type', 'category', 'language']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_templates');
    }
};