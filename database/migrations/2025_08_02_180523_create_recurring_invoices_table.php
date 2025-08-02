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
        Schema::create('recurring_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('template_id')->nullable()->constrained('invoice_templates')->onDelete('set null');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly']);
            $table->integer('interval')->default(1); // Every X frequency
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_invoice_date');
            $table->integer('max_invoices')->nullable();
            $table->integer('invoices_generated')->default(0);
            $table->decimal('amount', 15, 2);
            $table->string('language', 2)->default('en');
            $table->boolean('is_active')->default(true);
            $table->json('invoice_data'); // Template data for generating invoices
            $table->timestamps();
            
            $table->index(['customer_id', 'is_active']);
            $table->index('next_invoice_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_invoices');
    }
};
