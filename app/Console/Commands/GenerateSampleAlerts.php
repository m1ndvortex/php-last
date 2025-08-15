<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Alert;
use App\Models\InventoryItem;
use App\Models\Customer;
use App\Models\Invoice;

class GenerateSampleAlerts extends Command
{
    protected $signature = 'alerts:generate-sample';
    protected $description = 'Generate sample alerts for dashboard testing';

    public function handle()
    {
        $this->info('Generating sample alerts...');

        // Clear existing alerts
        Alert::truncate();

        // Create low stock alerts
        $this->createLowStockAlerts();

        // Create overdue payment alerts
        $this->createOverduePaymentAlerts();

        // Create birthday reminder alerts
        $this->createBirthdayAlerts();

        $this->info('Sample alerts generated successfully!');
    }

    private function createLowStockAlerts()
    {
        // Get some inventory items or create sample ones
        $items = InventoryItem::take(3)->get();
        
        if ($items->isEmpty()) {
            $this->warn('No inventory items found. Creating sample low stock alerts with dummy data.');
            
            Alert::create([
                'type' => 'low_stock',
                'priority' => 'medium',
                'title' => 'Low Stock Alert',
                'message' => '5 items are running low on stock',
                'reference_type' => 'inventory_item',
                'reference_id' => 1,
                'status' => 'active',
                'created_by' => 1,
                'metadata' => [
                    'item_count' => 5,
                    'threshold' => 10,
                ],
            ]);
        } else {
            foreach ($items as $item) {
                Alert::create([
                    'type' => 'low_stock',
                    'priority' => 'medium',
                    'title' => 'Low Stock Alert',
                    'message' => "Low stock: {$item->name} has {$item->quantity} units remaining",
                    'reference_type' => 'inventory_item',
                    'reference_id' => $item->id,
                    'status' => 'active',
                    'created_by' => 1,
                    'metadata' => [
                        'item_name' => $item->name,
                        'current_quantity' => $item->quantity,
                        'sku' => $item->sku,
                    ],
                ]);
            }
        }
    }

    private function createOverduePaymentAlerts()
    {
        // Get some overdue invoices or create sample ones
        $invoices = Invoice::where('status', '!=', 'paid')
            ->where('due_date', '<', now())
            ->take(2)
            ->get();

        if ($invoices->isEmpty()) {
            $this->warn('No overdue invoices found. Creating sample overdue payment alert.');
            
            Alert::create([
                'type' => 'overdue_payment',
                'priority' => 'high',
                'title' => 'Pending Cheque',
                'message' => 'Cheque #CH-2024-001 due tomorrow',
                'reference_type' => 'invoice',
                'reference_id' => 1,
                'status' => 'active',
                'created_by' => 1,
                'metadata' => [
                    'cheque_number' => 'CH-2024-001',
                    'due_date' => now()->addDay()->toDateString(),
                ],
            ]);
        } else {
            foreach ($invoices as $invoice) {
                Alert::create([
                    'type' => 'overdue_payment',
                    'priority' => 'high',
                    'title' => 'Overdue Payment',
                    'message' => "Payment overdue: Invoice #{$invoice->invoice_number}",
                    'reference_type' => 'invoice',
                    'reference_id' => $invoice->id,
                    'status' => 'active',
                    'created_by' => 1,
                    'metadata' => [
                        'invoice_number' => $invoice->invoice_number,
                        'amount' => $invoice->total_amount,
                        'days_overdue' => now()->diffInDays($invoice->due_date),
                    ],
                ]);
            }
        }
    }

    private function createBirthdayAlerts()
    {
        // Create a sample birthday alert
        Alert::create([
            'type' => 'birthday_reminder',
            'priority' => 'low',
            'title' => 'Items Expiring Soon',
            'message' => '3 items will expire within 7 days',
            'reference_type' => 'inventory_item',
            'reference_id' => 1,
            'status' => 'active',
            'created_by' => 1,
            'metadata' => [
                'expiring_count' => 3,
                'days_until_expiry' => 7,
            ],
        ]);
    }
}