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
        Schema::table('inventory_items', function (Blueprint $table) {
            // Composite index for category and location filtering with status
            $table->index(['category_id', 'location_id', 'is_active'], 'idx_inventory_category_location_active');
            
            // Composite index for gold purity and weight queries
            $table->index(['gold_purity', 'weight'], 'idx_inventory_gold_weight');
            
            // Composite index for stock level queries
            $table->index(['quantity', 'minimum_stock'], 'idx_inventory_stock_levels');
            
            // Composite index for date-based queries
            $table->index(['created_at', 'updated_at'], 'idx_inventory_dates');
            
            // Index for SKU searches (already unique but adding for performance)
            $table->index(['sku', 'is_active'], 'idx_inventory_sku_active');
            
            // Index for main category filtering
            if (Schema::hasColumn('inventory_items', 'main_category_id')) {
                $table->index(['main_category_id', 'category_id'], 'idx_inventory_main_sub_category');
            }
        });

        // Add composite indexes to invoices table
        Schema::table('invoices', function (Blueprint $table) {
            // Composite index for customer and status filtering
            $table->index(['customer_id', 'status', 'issue_date'], 'idx_invoices_customer_status_date');
            
            // Composite index for date range and status queries
            $table->index(['issue_date', 'due_date', 'status'], 'idx_invoices_dates_status');
            
            // Composite index for language and status
            $table->index(['language', 'status'], 'idx_invoices_language_status');
            
            // Composite index for amount-based queries
            $table->index(['total_amount', 'status'], 'idx_invoices_amount_status');
            
            // Index for payment tracking
            $table->index(['paid_at', 'status'], 'idx_invoices_payment_tracking');
        });

        // Add composite indexes to customers table
        Schema::table('customers', function (Blueprint $table) {
            // Composite index for active customers by type
            $table->index(['is_active', 'customer_type'], 'idx_customers_active_type');
            
            // Composite index for CRM pipeline
            $table->index(['crm_stage', 'is_active'], 'idx_customers_crm_active');
            
            // Composite index for language and type
            $table->index(['preferred_language', 'customer_type'], 'idx_customers_language_type');
            
            // Composite index for birthday reminders
            $table->index(['birthday', 'is_active'], 'idx_customers_birthday_active');
            
            // Composite index for anniversary reminders
            $table->index(['anniversary', 'is_active'], 'idx_customers_anniversary_active');
            
            // Index for search optimization
            $table->index(['name', 'is_active'], 'idx_customers_name_active');
        });

        // Add composite indexes to invoice_items table
        Schema::table('invoice_items', function (Blueprint $table) {
            // Composite index for invoice and inventory item
            $table->index(['invoice_id', 'inventory_item_id'], 'idx_invoice_items_invoice_inventory');
            
            // Composite index for gold jewelry queries
            $table->index(['gold_purity', 'weight'], 'idx_invoice_items_gold_weight');
            
            // Index for quantity and price analysis
            $table->index(['quantity', 'unit_price'], 'idx_invoice_items_quantity_price');
        });

        // Add indexes to communications table if it exists
        if (Schema::hasTable('communications')) {
            Schema::table('communications', function (Blueprint $table) {
                // Composite index for customer communications
                $table->index(['customer_id', 'created_at'], 'idx_communications_customer_date');
                
                // Index for communication type and status
                if (Schema::hasColumn('communications', 'type')) {
                    $table->index(['type', 'created_at'], 'idx_communications_type_date');
                }
            });
        }

        // Add indexes to inventory_movements table if it exists
        if (Schema::hasTable('inventory_movements')) {
            Schema::table('inventory_movements', function (Blueprint $table) {
                // Composite index for item movements
                $table->index(['inventory_item_id', 'created_at'], 'idx_movements_item_date');
                
                // Index for movement type analysis
                if (Schema::hasColumn('inventory_movements', 'type')) {
                    $table->index(['type', 'created_at'], 'idx_movements_type_date');
                }
            });
        }

        // Add indexes to categories table for hierarchy queries
        if (Schema::hasTable('categories')) {
            Schema::table('categories', function (Blueprint $table) {
                // Index for parent-child relationships
                if (Schema::hasColumn('categories', 'parent_id')) {
                    $table->index(['parent_id', 'is_active'], 'idx_categories_parent_active');
                }
                
                // Index for category hierarchy
                if (Schema::hasColumn('categories', 'level')) {
                    $table->index(['level', 'parent_id'], 'idx_categories_level_parent');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes from inventory_items table
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropIndex('idx_inventory_category_location_active');
            $table->dropIndex('idx_inventory_gold_weight');
            $table->dropIndex('idx_inventory_stock_levels');
            $table->dropIndex('idx_inventory_dates');
            $table->dropIndex('idx_inventory_sku_active');
            
            if (Schema::hasColumn('inventory_items', 'main_category_id')) {
                $table->dropIndex('idx_inventory_main_sub_category');
            }
        });

        // Drop indexes from invoices table
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('idx_invoices_customer_status_date');
            $table->dropIndex('idx_invoices_dates_status');
            $table->dropIndex('idx_invoices_language_status');
            $table->dropIndex('idx_invoices_amount_status');
            $table->dropIndex('idx_invoices_payment_tracking');
        });

        // Drop indexes from customers table
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex('idx_customers_active_type');
            $table->dropIndex('idx_customers_crm_active');
            $table->dropIndex('idx_customers_language_type');
            $table->dropIndex('idx_customers_birthday_active');
            $table->dropIndex('idx_customers_anniversary_active');
            $table->dropIndex('idx_customers_name_active');
        });

        // Drop indexes from invoice_items table
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropIndex('idx_invoice_items_invoice_inventory');
            $table->dropIndex('idx_invoice_items_gold_weight');
            $table->dropIndex('idx_invoice_items_quantity_price');
        });

        // Drop indexes from communications table if it exists
        if (Schema::hasTable('communications')) {
            Schema::table('communications', function (Blueprint $table) {
                $table->dropIndex('idx_communications_customer_date');
                
                if (Schema::hasColumn('communications', 'type')) {
                    $table->dropIndex('idx_communications_type_date');
                }
            });
        }

        // Drop indexes from inventory_movements table if it exists
        if (Schema::hasTable('inventory_movements')) {
            Schema::table('inventory_movements', function (Blueprint $table) {
                $table->dropIndex('idx_movements_item_date');
                
                if (Schema::hasColumn('inventory_movements', 'type')) {
                    $table->dropIndex('idx_movements_type_date');
                }
            });
        }

        // Drop indexes from categories table
        if (Schema::hasTable('categories')) {
            Schema::table('categories', function (Blueprint $table) {
                if (Schema::hasColumn('categories', 'parent_id')) {
                    $table->dropIndex('idx_categories_parent_active');
                }
                
                if (Schema::hasColumn('categories', 'level')) {
                    $table->dropIndex('idx_categories_level_parent');
                }
            });
        }
    }
};