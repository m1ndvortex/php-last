<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_deletion_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // full_deletion, partial_deletion, customer_data, etc.
            $table->json('data_types'); // Array of data types to delete
            $table->json('filters')->nullable(); // Deletion filters
            $table->string('status'); // pending, approved, processing, completed, rejected
            $table->text('reason')->nullable(); // Reason for deletion request
            $table->json('backup_info')->nullable(); // Information about backed up data
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->timestamp('scheduled_for')->nullable(); // When deletion should occur
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('deletion_summary')->nullable(); // Summary of what was deleted
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['status', 'scheduled_for']);
            $table->index(['approved_by', 'approved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_deletion_requests');
    }
};