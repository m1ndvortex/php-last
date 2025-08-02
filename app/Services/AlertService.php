<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InventoryItem;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class AlertService
{
    /**
     * Get all active alerts
     */
    public function getAlerts(): array
    {
        $cacheKey = 'dashboard_alerts';
        
        return Cache::remember($cacheKey, 300, function () {
            return [
                'pending_cheques' => $this->getPendingChequeAlerts(),
                'stock_warnings' => $this->getStockWarningAlerts(),
                'overdue_invoices' => $this->getOverdueInvoiceAlerts(),
                'expiring_items' => $this->getExpiringItemAlerts(),
                'low_stock' => $this->getLowStockAlerts(),
                'high_value_pending' => $this->getHighValuePendingAlerts()
            ];
        });
    }

    /**
     * Get pending cheque alerts
     */
    private function getPendingChequeAlerts(): array
    {
        $pendingCheques = Invoice::where('payment_method', 'cheque')
            ->whereIn('status', ['sent', 'overdue'])
            ->where('due_date', '<=', Carbon::now()->addDays(7))
            ->with('customer')
            ->get();

        return $pendingCheques->map(function ($invoice) {
            $daysOverdue = Carbon::now()->diffInDays($invoice->due_date, false);
            
            return [
                'id' => $invoice->id,
                'type' => 'pending_cheque',
                'severity' => $daysOverdue > 0 ? 'high' : 'medium',
                'title' => 'Pending Cheque Payment',
                'message' => "Cheque payment of {$invoice->total_amount} from {$invoice->customer->name} is " . 
                           ($daysOverdue > 0 ? "{$daysOverdue} days overdue" : "due in " . abs($daysOverdue) . " days"),
                'data' => [
                    'invoice_id' => $invoice->id,
                    'customer_name' => $invoice->customer->name,
                    'amount' => $invoice->total_amount,
                    'due_date' => $invoice->due_date,
                    'days_overdue' => $daysOverdue
                ],
                'created_at' => now()
            ];
        })->toArray();
    }

    /**
     * Get stock warning alerts
     */
    private function getStockWarningAlerts(): array
    {
        $lowStockItems = InventoryItem::where('quantity', '<=', 5)
            ->where('quantity', '>', 0)
            ->get();

        $outOfStockItems = InventoryItem::where('quantity', '<=', 0)->get();

        $alerts = [];

        // Low stock alerts
        foreach ($lowStockItems as $item) {
            $alerts[] = [
                'id' => "low_stock_{$item->id}",
                'type' => 'low_stock',
                'severity' => 'medium',
                'title' => 'Low Stock Warning',
                'message' => "Item '{$item->name}' has only {$item->quantity} units remaining",
                'data' => [
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'current_quantity' => $item->quantity,
                    'sku' => $item->sku
                ],
                'created_at' => now()
            ];
        }

        // Out of stock alerts
        foreach ($outOfStockItems as $item) {
            $alerts[] = [
                'id' => "out_of_stock_{$item->id}",
                'type' => 'out_of_stock',
                'severity' => 'high',
                'title' => 'Out of Stock',
                'message' => "Item '{$item->name}' is out of stock",
                'data' => [
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'sku' => $item->sku
                ],
                'created_at' => now()
            ];
        }

        return $alerts;
    }

    /**
     * Get overdue invoice alerts
     */
    private function getOverdueInvoiceAlerts(): array
    {
        $overdueInvoices = Invoice::where('status', 'overdue')
            ->where('due_date', '<', Carbon::now())
            ->with('customer')
            ->get();

        return $overdueInvoices->map(function ($invoice) {
            $daysOverdue = Carbon::now()->diffInDays($invoice->due_date);
            
            return [
                'id' => "overdue_{$invoice->id}",
                'type' => 'overdue_invoice',
                'severity' => $daysOverdue > 30 ? 'high' : 'medium',
                'title' => 'Overdue Invoice',
                'message' => "Invoice #{$invoice->invoice_number} from {$invoice->customer->name} is {$daysOverdue} days overdue",
                'data' => [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'customer_name' => $invoice->customer->name,
                    'amount' => $invoice->total_amount,
                    'due_date' => $invoice->due_date,
                    'days_overdue' => $daysOverdue
                ],
                'created_at' => now()
            ];
        })->toArray();
    }

    /**
     * Get expiring item alerts
     */
    private function getExpiringItemAlerts(): array
    {
        $expiringItems = InventoryItem::whereNotNull('expiry_date')
            ->where('expiry_date', '<=', Carbon::now()->addDays(30))
            ->where('expiry_date', '>', Carbon::now())
            ->get();

        return $expiringItems->map(function ($item) {
            $daysUntilExpiry = Carbon::now()->diffInDays($item->expiry_date);
            
            return [
                'id' => "expiring_{$item->id}",
                'type' => 'expiring_item',
                'severity' => $daysUntilExpiry <= 7 ? 'high' : 'medium',
                'title' => 'Item Expiring Soon',
                'message' => "Item '{$item->name}' expires in {$daysUntilExpiry} days",
                'data' => [
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'expiry_date' => $item->expiry_date,
                    'days_until_expiry' => $daysUntilExpiry,
                    'quantity' => $item->quantity
                ],
                'created_at' => now()
            ];
        })->toArray();
    }

    /**
     * Get low stock alerts (different threshold than stock warnings)
     */
    private function getLowStockAlerts(): array
    {
        $lowStockItems = InventoryItem::where('quantity', '<=', 10)
            ->where('quantity', '>', 5)
            ->get();

        return $lowStockItems->map(function ($item) {
            return [
                'id' => "low_stock_alert_{$item->id}",
                'type' => 'low_stock_alert',
                'severity' => 'low',
                'title' => 'Stock Running Low',
                'message' => "Consider restocking '{$item->name}' - {$item->quantity} units remaining",
                'data' => [
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'current_quantity' => $item->quantity,
                    'sku' => $item->sku
                ],
                'created_at' => now()
            ];
        })->toArray();
    }

    /**
     * Get high value pending invoice alerts
     */
    private function getHighValuePendingAlerts(): array
    {
        $highValueThreshold = 50000; // Configurable threshold
        
        $highValueInvoices = Invoice::whereIn('status', ['sent', 'overdue'])
            ->where('total_amount', '>=', $highValueThreshold)
            ->with('customer')
            ->get();

        return $highValueInvoices->map(function ($invoice) {
            return [
                'id' => "high_value_{$invoice->id}",
                'type' => 'high_value_pending',
                'severity' => 'medium',
                'title' => 'High Value Pending Invoice',
                'message' => "High value invoice #{$invoice->invoice_number} ({$invoice->total_amount}) is pending payment",
                'data' => [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'customer_name' => $invoice->customer->name,
                    'amount' => $invoice->total_amount,
                    'issue_date' => $invoice->issue_date
                ],
                'created_at' => now()
            ];
        })->toArray();
    }

    /**
     * Get alert count by severity
     */
    public function getAlertCounts(): array
    {
        $alerts = $this->getAlerts();
        $allAlerts = collect($alerts)->flatten(1);

        return [
            'total' => $allAlerts->count(),
            'high' => $allAlerts->where('severity', 'high')->count(),
            'medium' => $allAlerts->where('severity', 'medium')->count(),
            'low' => $allAlerts->where('severity', 'low')->count()
        ];
    }

    /**
     * Mark alert as read (for future implementation)
     */
    public function markAsRead(string $alertId): bool
    {
        // This would typically update a user_alerts table
        // For now, we'll just clear the cache to refresh alerts
        $this->clearCache();
        return true;
    }

    /**
     * Clear alerts cache
     */
    public function clearCache(): void
    {
        Cache::forget('dashboard_alerts');
    }
}