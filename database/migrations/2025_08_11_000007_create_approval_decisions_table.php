<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('approval_requests')->onDelete('cascade');
            $table->foreignId('step_id')->constrained('approval_steps');
            $table->foreignId('user_id')->constrained('users');
            $table->enum('decision', ['approved', 'rejected', 'delegated']);
            $table->text('comments')->nullable();
            $table->timestamp('approved_at');
            $table->timestamps();

            $table->index(['request_id', 'step_id']);
            $table->index(['user_id', 'decision']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_decisions');
    }
};