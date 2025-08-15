<?php

namespace App\Services;

use App\Models\User;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;

class QuickActionService
{
    /**
     * Get available quick actions based on user permissions and system state
     */
    public function getQuickActions(): array
    {
        $user = Auth::user();
        $actions = [];

        // Base actions available to all users
        $baseActions = [
            [
                'key' => 'add_customer',
                'label' => 'Add Customer',
                'icon' => 'UserGroupIcon',
                'route' => '/customers/new',
                'description' => 'Create a new customer record',
                'enabled' => true,
                'badge' => null,
            ],
            [
                'key' => 'add_inventory',
                'label' => 'Add Item',
                'icon' => 'ArchiveBoxIcon',
                'route' => '/inventory/new',
                'description' => 'Add new inventory item',
                'enabled' => true,
                'badge' => null,
            ],
            [
                'key' => 'create_invoice',
                'label' => 'Create Invoice',
                'icon' => 'DocumentTextIcon',
                'route' => '/invoices/new',
                'description' => 'Generate a new invoice',
                'enabled' => $this->hasCustomers(),
                'badge' => null,
            ],
            [
                'key' => 'view_reports',
                'label' => 'View Reports',
                'icon' => 'ChartBarIcon',
                'route' => '/reports',
                'description' => 'Access business reports',
                'enabled' => true,
                'badge' => null,
            ],
            [
                'key' => 'accounting',
                'label' => 'Accounting',
                'icon' => 'CalculatorIcon',
                'route' => '/accounting',
                'description' => 'Manage financial records',
                'enabled' => true,
                'badge' => $this->getPendingTransactionsBadge(),
            ],
            [
                'key' => 'settings',
                'label' => 'Settings',
                'icon' => 'CogIcon',
                'route' => '/settings',
                'description' => 'Configure system settings',
                'enabled' => true,
                'badge' => null,
            ],
        ];

        // Add contextual actions based on system state
        $contextualActions = $this->getContextualActions();

        return array_merge($baseActions, $contextualActions);
    }

    /**
     * Get contextual actions based on current system state
     */
    private function getContextualActions(): array
    {
        $actions = [];

        // Low stock alert action
        $lowStockCount = $this->getLowStockItemsCount();
        if ($lowStockCount > 0) {
            $actions[] = [
                'key' => 'low_stock_items',
                'label' => 'Low Stock Items',
                'icon' => 'ExclamationTriangleIcon',
                'route' => '/inventory?filter=low_stock',
                'description' => 'Review items with low stock',
                'enabled' => true,
                'badge' => $lowStockCount,
                'priority' => 'high',
            ];
        }

        // Overdue invoices action
        $overdueCount = $this->getOverdueInvoicesCount();
        if ($overdueCount > 0) {
            $actions[] = [
                'key' => 'overdue_invoices',
                'label' => 'Overdue Invoices',
                'icon' => 'ClockIcon',
                'route' => '/invoices?status=overdue',
                'description' => 'Follow up on overdue payments',
                'enabled' => true,
                'badge' => $overdueCount,
                'priority' => 'high',
            ];
        }

        // Pending approvals (if any)
        $pendingApprovals = $this->getPendingApprovalsCount();
        if ($pendingApprovals > 0) {
            $actions[] = [
                'key' => 'pending_approvals',
                'label' => 'Pending Approvals',
                'icon' => 'CheckCircleIcon',
                'route' => '/approvals',
                'description' => 'Review pending approvals',
                'enabled' => true,
                'badge' => $pendingApprovals,
                'priority' => 'medium',
            ];
        }

        return $actions;
    }

    /**
     * Get quick action statistics
     */
    public function getQuickActionStats(): array
    {
        return [
            'total_customers' => Customer::count(),
            'total_inventory_items' => InventoryItem::count(),
            'pending_invoices' => Invoice::where('status', 'pending')->count(),
            'low_stock_items' => $this->getLowStockItemsCount(),
            'overdue_invoices' => $this->getOverdueInvoicesCount(),
            'recent_activities_count' => $this->getRecentActivitiesCount(),
        ];
    }

    /**
     * Check if system has customers (required for creating invoices)
     */
    private function hasCustomers(): bool
    {
        return Customer::count() > 0;
    }

    /**
     * Get count of low stock items
     */
    private function getLowStockItemsCount(): int
    {
        return InventoryItem::whereColumn('quantity', '<=', 'reorder_level')
            ->where('reorder_level', '>', 0)
            ->count();
    }

    /**
     * Get count of overdue invoices
     */
    private function getOverdueInvoicesCount(): int
    {
        return Invoice::where('due_date', '<', now())
            ->where('status', '!=', 'paid')
            ->count();
    }

    /**
     * Get count of pending approvals
     */
    private function getPendingApprovalsCount(): int
    {
        // This would depend on your approval system implementation
        // For now, return 0 as placeholder
        return 0;
    }

    /**
     * Get pending transactions badge count
     */
    private function getPendingTransactionsBadge(): ?int
    {
        // You can implement logic to show pending transactions
        // For now, return null (no badge)
        return null;
    }

    /**
     * Get count of recent activities
     */
    private function getRecentActivitiesCount(): int
    {
        $recentThreshold = now()->subHours(24);
        
        $invoiceCount = Invoice::where('created_at', '>=', $recentThreshold)->count();
        $customerCount = Customer::where('created_at', '>=', $recentThreshold)->count();
        $inventoryCount = InventoryItem::where('updated_at', '>=', $recentThreshold)->count();
        
        return $invoiceCount + $customerCount + $inventoryCount;
    }
}