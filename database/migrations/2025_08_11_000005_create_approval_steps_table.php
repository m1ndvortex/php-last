<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('approval_workflows')->onDelete('cascade');
            $table->integer('step_order');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('approver_type', ['user', 'role', 'amount_based', 'department']);
            $table->unsignedBigInteger('approver_id')->nullable(); // user_id or role_id
            $table->integer('required_approvals')->default(1);
            $table->json('conditions')->nullable(); // Additional conditions for this step
            $table->boolean('is_parallel')->default(false); // Can multiple approvers approve simultaneously
            $table->integer('timeout_hours')->default(72); // Hours before escalation
            $table->timestamps();

            $table->index(['workflow_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_steps');
    }
};