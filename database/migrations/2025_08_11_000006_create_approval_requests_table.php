<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('approval_workflows');
            $table->morphs('approvable'); // The model being approved (transaction, invoice, etc.)
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->integer('current_step')->default(1);
            $table->foreignId('requested_by')->constrained('users');
            $table->timestamp('requested_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'current_step']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};