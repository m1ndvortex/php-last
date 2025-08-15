<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\Customer;
use App\Models\Alert;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AlertService
{
    /**
     * Check reorder levels for inventory items
     */
    public function checkReorderLevels(int $inventoryItemId): void
    {
        $item = InventoryItem::find($inventoryItemId);
        
        if (!$item) {
            return;
        }

        // Check if item is below reorder level
        if ($item->quantity <= $item->reorder_level && $item->reorder_level > 0) {
            $this->createLowStockAlert($item);
        }

        // Check if item is critically low (below minimum stock level)
        if ($item->quantity <= ($item->reorder_level * 0.5) && $item->reorder_level > 0) {
            $this->createCriticalStockAlert($item);
        }
    }

    /**
     * Create low stock alert
     */
    public function createLowStockAlert(InventoryItem $item): void
    {
        // Check if alert already exists for this item
        $existingAlert = Alert::where('type', 'low_stock')
            ->where('reference_type', 'inventory_item')
            ->where('reference_id', $item->id)
            ->where('status', 'active')
            ->first();

        if ($existingAlert) {
            // Update existing alert
            $existingAlert->update([
                'message' => "Low stock alert: {$item->name} has {$item->quantity} units remaining (reorder level: {$item->reorder_level})",
                'updated_at' => now(),
            ]);
        } else {
            // Create new alert
            Alert::create([
                'type' => 'low_stock',
                'priority' => 'medium',
                'title' => 'Low Stock Alert',
                'message' => "Low stock alert: {$item->name} has {$item->quantity} units remaining (reorder level: {$item->reorder_level})",
                'reference_type' => 'inventory_item',
                'reference_id' => $item->id,
                'status' => 'active',
                'created_by' => auth()->id(),
                'metadata' => [
                    'item_name' => $item->name,
                    'current_quantity' => $item->quantity,
                    'reorder_level' => $item->reorder_level,
                    'sku' => $item->sku,
                ],
            ]);
        }

        Log::info('Low stock alert created/updated', [
            'item_id' => $item->id,
            'item_name' => $item->name,
            'current_quantity' => $item->quantity,
            'reorder_level' => $item->reorder_level,
        ]);
    }

    /**
     * Create critical stock alert
     */
    public function createCriticalStockAlert(InventoryItem $item): void
    {
        Alert::create([
            'type' => 'critical_stock',
            'priority' => 'high',
            'title' => 'Critical Stock Alert',
            'message' => "CRITICAL: {$item->name} has only {$item->quantity} units remaining!",
            'reference_type' => 'inventory_item',
            'reference_id' => $item->id,
            'status' => 'active',
            'created_by' => auth()->id(),
            'metadata' => [
                'item_name' => $item->name,
                'current_quantity' => $item->quantity,
                'reorder_level' => $item->reorder_level,
                'sku' => $item->sku,
            ],
        ]);

        Log::warning('Critical stock alert created', [
            'item_id' => $item->id,
            'item_name' => $item->name,
            'current_quantity' => $item->quantity,
        ]);
    }

    /**
     * Create customer payment overdue alert
     */
    public function createOverduePaymentAlert(Customer $customer, $invoice): void
    {
        Alert::create([
            'type' => 'overdue_payment',
            'priority' => 'high',
            'title' => 'Overdue Payment Alert',
            'message' => "Payment overdue: {$customer->name} - Invoice #{$invoice->invoice_number} (Due: {$invoice->due_date->format('Y-m-d')})",
            'reference_type' => 'invoice',
            'reference_id' => $invoice->id,
            'status' => 'active',
            'created_by' => auth()->id(),
            'metadata' => [
                'customer_name' => $customer->name,
                'customer_id' => $customer->id,
                'invoice_number' => $invoice->invoice_number,
                'due_date' => $invoice->due_date->toDateString(),
                'amount' => $invoice->total_amount,
                'days_overdue' => now()->diffInDays($invoice->due_date),
            ],
        ]);

        Log::info('Overdue payment alert created', [
            'customer_id' => $customer->id,
            'invoice_id' => $invoice->id,
            'days_overdue' => now()->diffInDays($invoice->due_date),
        ]);
    }

    /**
     * Create birthday reminder alert
     */
    public function createBirthdayReminderAlert(Customer $customer): void
    {
        Alert::create([
            'type' => 'birthday_reminder',
            'priority' => 'low',
            'title' => 'Birthday Reminder',
            'message' => "Upcoming birthday: {$customer->name} - {$customer->birthday->format('M d')}",
            'reference_type' => 'customer',
            'reference_id' => $customer->id,
            'status' => 'active',
            'created_by' => auth()->id(),
            'metadata' => [
                'customer_name' => $customer->name,
                'birthday' => $customer->birthday->toDateString(),
                'days_until_birthday' => now()->diffInDays($customer->birthday->setYear(now()->year)),
            ],
        ]);
    }

    /**
     * Resolve alert
     */
    public function resolveAlert(int $alertId, string $resolution = null): bool
    {
        $alert = Alert::find($alertId);
        
        if (!$alert) {
            return false;
        }

        $alert->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => auth()->id(),
            'resolution' => $resolution,
        ]);

        Log::info('Alert resolved', [
            'alert_id' => $alertId,
            'type' => $alert->type,
            'resolved_by' => auth()->id(),
        ]);

        return true;
    }

    /**
     * Get active alerts
     */
    public function getActiveAlerts(array $filters = [])
    {
        $query = Alert::where('status', 'active')
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc');

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        return $query->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Check for overdue invoices and create alerts
     */
    public function checkOverdueInvoices(): void
    {
        $overdueInvoices = \App\Models\Invoice::with('customer')
            ->where('due_date', '<', now())
            ->where('status', '!=', 'paid')
            ->whereDoesntHave('alerts', function ($query) {
                $query->where('type', 'overdue_payment')
                    ->where('status', 'active');
            })
            ->get();

        foreach ($overdueInvoices as $invoice) {
            $this->createOverduePaymentAlert($invoice->customer, $invoice);
        }
    }

    /**
     * Check for upcoming birthdays and create alerts
     */
    public function checkUpcomingBirthdays(): void
    {
        $upcomingBirthdays = Customer::active()
            ->whereNotNull('birthday')
            ->get()
            ->filter(function ($customer) {
                $birthday = $customer->birthday->setYear(now()->year);
                $daysUntil = now()->diffInDays($birthday, false);
                return $daysUntil >= 0 && $daysUntil <= 7; // Next 7 days
            });

        foreach ($upcomingBirthdays as $customer) {
            // Check if alert already exists
            $existingAlert = Alert::where('type', 'birthday_reminder')
                ->where('reference_type', 'customer')
                ->where('reference_id', $customer->id)
                ->where('status', 'active')
                ->whereDate('created_at', now())
                ->first();

            if (!$existingAlert) {
                $this->createBirthdayReminderAlert($customer);
            }
        }
    }

    /**
     * Get alerts for dashboard
     */
    public function getAlerts(int $limit = 10, int $offset = 0): array
    {
        $alerts = Alert::active()
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->limit($limit)
            ->get()
            ->map(function ($alert) {
                return [
                    'id' => $alert->id,
                    'type' => $alert->type,
                    'title' => $alert->title,
                    'message' => $alert->message,
                    'severity' => $this->mapPriorityToSeverity($alert->priority),
                    'timestamp' => $alert->created_at->toISOString(),
                    'read' => false, // Dashboard alerts are always unread initially
                    'action_url' => $this->getActionUrl($alert),
                    'action_label' => $this->getActionLabel($alert),
                ];
            })
            ->toArray();

        return $alerts;
    }

    /**
     * Get total count of active alerts
     */
    public function getTotalAlertsCount(): int
    {
        return Alert::active()->count();
    }

    /**
     * Get alert counts by priority
     */
    public function getAlertCounts(): array
    {
        $counts = Alert::active()
            ->selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();

        return [
            'total' => array_sum($counts),
            'high' => $counts['high'] ?? 0,
            'medium' => $counts['medium'] ?? 0,
            'low' => $counts['low'] ?? 0,
        ];
    }

    /**
     * Mark alert as read (for dashboard)
     */
    public function markAsRead(string $alertId): bool
    {
        $alert = Alert::find($alertId);
        
        if (!$alert) {
            return false;
        }

        // For now, we'll resolve the alert when marked as read
        // In a more complex system, you might have a separate 'read' status
        return $this->resolveAlert($alert->id, 'Marked as read from dashboard');
    }

    /**
     * Clear cache for alerts
     */
    public function clearCache(): void
    {
        // If you're using cache for alerts, clear it here
        // For now, this is a placeholder
    }

    /**
     * Map priority to severity for frontend
     */
    private function mapPriorityToSeverity(string $priority): string
    {
        return match ($priority) {
            'high' => 'critical',
            'medium' => 'high',
            'low' => 'medium',
            default => 'low',
        };
    }

    /**
     * Get action URL for alert
     */
    private function getActionUrl(Alert $alert): ?string
    {
        return match ($alert->type) {
            'low_stock', 'critical_stock' => '/inventory',
            'overdue_payment' => '/invoices?status=overdue',
            'birthday_reminder' => '/customers',
            default => null,
        };
    }

    /**
     * Get action label for alert
     */
    private function getActionLabel(Alert $alert): ?string
    {
        return match ($alert->type) {
            'low_stock', 'critical_stock' => 'View Inventory',
            'overdue_payment' => 'View Invoices',
            'birthday_reminder' => 'View Customers',
            default => null,
        };
    }
}