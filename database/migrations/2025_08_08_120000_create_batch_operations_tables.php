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
        Schema::create('batch_operations', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // invoice_generation, pdf_generation, communication_sending
            $table->enum('status', ['processing', 'completed', 'completed_with_errors', 'failed'])->default('processing');
            $table->decimal('progress', 5, 2)->default(0); // 0-100
            $table->integer('processed_count')->default(0);
            $table->integer('total_count')->default(0);
            $table->json('metadata')->nullable(); // Store operation-specific data
            $table->json('summary')->nullable(); // Store final results summary
            $table->text('error_message')->nullable();
            $table->string('combined_file_path')->nullable(); // For combined PDFs
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['type', 'status']);
            $table->index(['created_by', 'created_at']);
            $table->index('status');
        });

        Schema::create('batch_operation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_operation_id')->constrained()->onDelete('cascade');
            $table->string('reference_type'); // invoice, customer, etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->json('data')->nullable(); // Store item-specific data
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['batch_operation_id', 'status']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('customer_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_operation_items');
        Schema::dropIfExists('batch_operations');
    }
};