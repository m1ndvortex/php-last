<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_persian')->nullable();
            $table->text('description')->nullable();
            $table->integer('budget_year');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['draft', 'approved', 'active', 'closed', 'superseded'])->default('draft');
            $table->string('currency', 3)->default('USD');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('parent_budget_id')->nullable()->constrained('budgets');
            $table->integer('revision_number')->default(0);
            $table->timestamps();

            $table->index(['budget_year', 'status']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};