<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Alert;
use App\Models\User;

class GenerateSampleNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:generate-samples';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate sample notifications for testing the notification system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating sample notifications...');

        // Get the first user or create one
        $user = User::first();
        if (!$user) {
            $this->info('No users found. Creating test user...');
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now()
            ]);
        }

        // Clear existing alerts
        Alert::truncate();

        // Create sample alerts
        $alerts = [
            [
                'type' => 'low_stock',
                'priority' => 'high',
                'title' => 'Low Stock Alert',
                'message' => 'Gold Ring inventory is running low (5 units remaining)',
                'reference_type' => 'inventory_item',
                'reference_id' => 1,
                'status' => 'active',
                'created_by' => $user->id,
                'metadata' => [
                    'item_name' => 'Gold Ring',
                    'current_quantity' => 5,
                    'reorder_level' => 10,
                    'sku' => 'GR-001'
                ]
            ],
            [
                'type' => 'overdue_payment',
                'priority' => 'high',
                'title' => 'Overdue Payment',
                'message' => 'Invoice #INV-2024-001 payment is overdue by 5 days',
                'reference_type' => 'invoice',
                'reference_id' => 1,
                'status' => 'active',
                'created_by' => $user->id,
                'metadata' => [
                    'invoice_number' => 'INV-2024-001',
                    'customer_name' => 'John Doe',
                    'amount' => 1500.00,
                    'days_overdue' => 5
                ]
            ],
            [
                'type' => 'birthday_reminder',
                'priority' => 'low',
                'title' => 'Birthday Reminder',
                'message' => 'Customer Sarah Johnson has a birthday tomorrow',
                'reference_type' => 'customer',
                'reference_id' => 1,
                'status' => 'active',
                'created_by' => $user->id,
                'metadata' => [
                    'customer_name' => 'Sarah Johnson',
                    'birthday' => '1985-08-17',
                    'days_until_birthday' => 1
                ]
            ],
            [
                'type' => 'system',
                'priority' => 'medium',
                'title' => 'System Maintenance',
                'message' => 'Scheduled maintenance will occur tonight at 2 AM',
                'status' => 'active',
                'created_by' => $user->id,
                'metadata' => [
                    'maintenance_time' => '2025-08-17 02:00:00',
                    'estimated_duration' => '30 minutes'
                ]
            ],
            [
                'type' => 'critical_stock',
                'priority' => 'high',
                'title' => 'Critical Stock Alert',
                'message' => 'Silver Necklace is critically low (2 units remaining)',
                'reference_type' => 'inventory_item',
                'reference_id' => 2,
                'status' => 'active',
                'created_by' => $user->id,
                'metadata' => [
                    'item_name' => 'Silver Necklace',
                    'current_quantity' => 2,
                    'reorder_level' => 15,
                    'sku' => 'SN-002'
                ]
            ],
            [
                'type' => 'expiring_item',
                'priority' => 'medium',
                'title' => 'Items Expiring Soon',
                'message' => 'Diamond Earrings will expire in 3 days',
                'reference_type' => 'inventory_item',
                'reference_id' => 3,
                'status' => 'active',
                'created_by' => $user->id,
                'metadata' => [
                    'item_name' => 'Diamond Earrings',
                    'expiry_date' => now()->addDays(3)->toDateString(),
                    'days_until_expiry' => 3
                ]
            ]
        ];

        foreach ($alerts as $alertData) {
            Alert::create($alertData);
        }

        $this->info('Sample notifications generated successfully!');
        $this->info('Total alerts created: ' . Alert::count());
        
        // Show alert counts by priority
        $counts = Alert::selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();
            
        $this->table(
            ['Priority', 'Count'],
            collect($counts)->map(fn($count, $priority) => [$priority, $count])->values()->toArray()
        );

        return 0;
    }
}
