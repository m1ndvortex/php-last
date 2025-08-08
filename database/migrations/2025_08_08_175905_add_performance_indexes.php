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
        // Add composite indexes to inventory_items table
        if (Schema::hasTable('inventory_items')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                // Composite index for category, location, and status filtering
                $table->index(['category_id', 'location_id', 'status'], 'idx_inventory_category_location_status');
                
                // Index for date-based queries
                $table->index(['created_at', 'updated_at'], 'idx_inventory_dates');
                
                // Index for gold purity and weight searches
                $table->index(['gold_purity', 'weight'], 'idx_inventory_gold_weight');
                
                // Index for stock level queries
                $table->index(['quantity', 'status'], 'idx_inventory_quantity_status');
                
                // Index for price range queries
                $table->index(['unit_price', 'cost_price'], 'idx_inventory_prices');
                
                // Index for active items with category
                $table->index(['is_active', 'category_id'], 'idx_inventory_active_category');
            });
        }

        // Add composite indexes to invoices table
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                // Composite index for customer and status filtering
                $table->index(['customer_id', 'status'], 'idx_invoices_customer_status');
                
                // Index for date range queries
                $table->index(['issue_date', 'due_date'], 'idx_invoices_dates');
                
                // Index for status and date filtering
                $table->index(['status', 'issue_date'], 'idx_invoices_status_date');
                
                // Index for payment status queries
                $table->index(['is_paid', 'due_date'], 'idx_invoices_payment_due');
                
                // Index for total amount queries
                $table->index(['total_amount', 'status'], 'idx_invoices_amount_status');
                
                // Index for language-specific queries
                $table->index(['language', 'status'], 'idx_invoices_language_status');
            });
        }

        // Add composite indexes to customers table
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                // Index for name searches (both English and Persian)
                $table->index(['first_name', 'last_name'], 'idx_customers_name');
                
                // Index for contact information searches
                $table->index(['email', 'phone'], 'idx_customers_contact');
                
                // Index for active customers
                $table->index(['is_active', 'created_at'], 'idx_customers_active_date');
                
                // Index for customer type and status
                $table->index(['customer_type', 'is_active'], 'idx_customers_type_active');
                
                // Index for location-based queries
                $table->index(['city', 'country'], 'idx_customers_location');
            });
        }

        // Add indexes to invoice_items table for better join performance
        if (Schema::hasTable('invoice_items')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                // Composite index for invoice and inventory item
                $table->index(['invoice_id', 'inventory_item_id'], 'idx_invoice_items_composite');
                
                // Index for quantity and price calculations
                $table->index(['quantity', 'unit_price'], 'idx_invoice_items_quantity_price');
                
                // Index for gold purity filtering in reports
                $table->index(['gold_purity', 'weight'], 'idx_invoice_items_gold');
            });
        }

        // Add indexes to inventory_movements table
        if (Schema::hasTable('inventory_movements')) {
            Schema::table('inventory_movements', function (Blueprint $table) {
                // Composite index for item and movement type
                $table->index(['inventory_item_id', 'movement_type'], 'idx_movements_item_type');
                
                // Index for date-based movement queries
                $table->index(['movement_date', 'movement_type'], 'idx_movements_date_type');
                
                // Index for quantity tracking
                $table->index(['quantity_change', 'movement_type'], 'idx_movements_quantity_type');
            });
        }

        // Add indexes to communications table
        if (Schema::hasTable('communications')) {
            Schema::table('communications', function (Blueprint $table) {
                // Composite index for customer and communication type
                $table->index(['customer_id', 'type'], 'idx_communications_customer_type');
                
                // Index for date and status filtering
                $table->index(['sent_at', 'status'], 'idx_communications_date_status');
                
                // Index for scheduled communications
                $table->index(['scheduled_at', 'status'], 'idx_communications_scheduled');
            });
        }

        // Add indexes to categories table for hierarchy queries
        if (Schema::hasTable('categories')) {
            Schema::table('categories', function (Blueprint $table) {
                // Index for parent-child relationships
                $table->index(['parent_id', 'is_active'], 'idx_categories_parent_active');
                
                // Index for ordering and active status
                $table->index(['sort_order', 'is_active'], 'idx_categories_order_active');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes from inventory_items table
        if (Schema::hasTable('inventory_items')) {
            Schema::table('inventory_items', function (Blueprint $table) {
                $table->dropIndex('idx_inventory_category_location_status');
                $table->dropIndex('idx_inventory_dates');
                $table->dropIndex('idx_inventory_gold_weight');
                $table->dropIndex('idx_inventory_quantity_status');
                $table->dropIndex('idx_inventory_prices');
                $table->dropIndex('idx_inventory_active_category');
            });
        }

        // Drop indexes from invoices table
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropIndex('idx_invoices_customer_status');
                $table->dropIndex('idx_invoices_dates');
                $table->dropIndex('idx_invoices_status_date');
                $table->dropIndex('idx_invoices_payment_due');
                $table->dropIndex('idx_invoices_amount_status');
                $table->dropIndex('idx_invoices_language_status');
            });
        }

        // Drop indexes from customers table
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropIndex('idx_customers_name');
                $table->dropIndex('idx_customers_contact');
                $table->dropIndex('idx_customers_active_date');
                $table->dropIndex('idx_customers_type_active');
                $table->dropIndex('idx_customers_location');
            });
        }

        // Drop indexes from invoice_items table
        if (Schema::hasTable('invoice_items')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->dropIndex('idx_invoice_items_composite');
                $table->dropIndex('idx_invoice_items_quantity_price');
                $table->dropIndex('idx_invoice_items_gold');
            });
        }

        // Drop indexes from inventory_movements table
        if (Schema::hasTable('inventory_movements')) {
            Schema::table('inventory_movements', function (Blueprint $table) {
                $table->dropIndex('idx_movements_item_type');
                $table->dropIndex('idx_movements_date_type');
                $table->dropIndex('idx_movements_quantity_type');
            });
        }

        // Drop indexes from communications table
        if (Schema::hasTable('communications')) {
            Schema::table('communications', function (Blueprint $table) {
                $table->dropIndex('idx_communications_customer_type');
                $table->dropIndex('idx_communications_date_status');
                $table->dropIndex('idx_communications_scheduled');
            });
        }

        // Drop indexes from categories table
        if (Schema::hasTable('categories')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropIndex('idx_categories_parent_active');
                $table->dropIndex('idx_categories_order_active');
            });
        }
    }
};
