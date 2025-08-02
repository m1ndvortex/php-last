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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->enum('preferred_language', ['en', 'fa'])->default('en');
            $table->enum('customer_type', ['retail', 'wholesale', 'vip'])->default('retail');
            $table->decimal('credit_limit', 10, 2)->default(0.00);
            $table->integer('payment_terms')->default(30);
            $table->text('notes')->nullable();
            $table->date('birthday')->nullable();
            $table->date('anniversary')->nullable();
            $table->boolean('is_active')->default(true);
            $table->enum('crm_stage', ['lead', 'prospect', 'customer', 'inactive'])->default('lead');
            $table->enum('lead_source', ['referral', 'website', 'social_media', 'walk_in', 'advertisement', 'other'])->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for better performance
            $table->index(['name', 'email', 'phone']);
            $table->index('customer_type');
            $table->index('crm_stage');
            $table->index('is_active');
            $table->index('preferred_language');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
