<?php

namespace App\Jobs;

use App\Models\InventoryItem;
use App\Models\User;
use App\Services\MessageTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendStockAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     *
     * @var int
     */
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $alertType = 'all', // 'low_stock', 'out_of_stock', 'expiring', 'all'
        public ?int $inventoryItemId = null
    ) {
        $this->onQueue('alerts');
    }

    /**
     * Execute the job.
     */
    public function handle(MessageTemplateService $templateService): void
    {
        try {
            Log::info('Processing stock alerts', [
                'alert_type' => $this->alertType,
                'inventory_item_id' => $this->inventoryItemId
            ]);

            if ($this->inventoryItemId) {
                // Process specific inventory item
                $item = InventoryItem::findOrFail($this->inventoryItemId);
                $this->processItemAlert($templateService, $item);
            } else {
                // Process all items based on alert type
                $this->processAllAlerts($templateService);
            }

            Log::info('Stock alerts processing completed');

        } catch (\Exception $e) {
            Log::error('SendStockAlertJob failed', [
                'alert_type' => $this->alertType,
                'inventory_item_id' => $this->inventoryItemId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Process all alerts based on type
     */
    private function processAllAlerts(MessageTemplateService $templateService): void
    {
        $alerts = [];

        // Low stock alerts
        if ($this->alertType === 'low_stock' || $this->alertType === 'all') {
            $lowStockItems = $this->getLowStockItems();
            if ($lowStockItems->count() > 0) {
                $alerts['low_stock'] = $lowStockItems;
                Log::info('Found low stock items', ['count' => $lowStockItems->count()]);
            }
        }

        // Out of stock alerts
        if ($this->alertType === 'out_of_stock' || $this->alertType === 'all') {
            $outOfStockItems = $this->getOutOfStockItems();
            if ($outOfStockItems->count() > 0) {
                $alerts['out_of_stock'] = $outOfStockItems;
                Log::info('Found out of stock items', ['count' => $outOfStockItems->count()]);
            }
        }

        // Expiring items alerts
        if ($this->alertType === 'expiring' || $this->alertType === 'all') {
            $expiringItems = $this->getExpiringItems();
            if ($expiringItems->count() > 0) {
                $alerts['expiring'] = $expiringItems;
                Log::info('Found expiring items', ['count' => $expiringItems->count()]);
            }
        }

        // Send consolidated alert if any alerts found
        if (!empty($alerts)) {
            $this->sendConsolidatedAlert($templateService, $alerts);
        }
    }

    /**
     * Process alert for specific item
     */
    private function processItemAlert(MessageTemplateService $templateService, InventoryItem $item): void
    {
        $alertTypes = [];

        // Check if item is low stock
        if ($item->quantity <= $item->minimum_stock && $item->quantity > 0) {
            $alertTypes[] = 'low_stock';
        }

        // Check if item is out of stock
        if ($item->quantity <= 0) {
            $alertTypes[] = 'out_of_stock';
        }

        // Check if item is expiring
        if ($item->expiry_date && Carbon::parse($item->expiry_date)->lte(Carbon::now()->addDays(7))) {
            $alertTypes[] = 'expiring';
        }

        // Send individual alerts
        foreach ($alertTypes as $alertType) {
            $this->sendIndividualAlert($templateService, $item, $alertType);
        }
    }

    /**
     * Get low stock items
     */
    private function getLowStockItems()
    {
        return InventoryItem::whereColumn('quantity', '<=', 'minimum_stock')
            ->where('quantity', '>', 0)
            ->with(['category', 'location'])
            ->get();
    }

    /**
     * Get out of stock items
     */
    private function getOutOfStockItems()
    {
        return InventoryItem::where('quantity', '<=', 0)
            ->with(['category', 'location'])
            ->get();
    }

    /**
     * Get expiring items
     */
    private function getExpiringItems()
    {
        return InventoryItem::whereNotNull('expiry_date')
            ->where('expiry_date', '<=', Carbon::now()->addDays(7))
            ->where('expiry_date', '>=', Carbon::now())
            ->with(['category', 'location'])
            ->get();
    }

    /**
     * Send consolidated alert with all alert types
     */
    private function sendConsolidatedAlert(MessageTemplateService $templateService, array $alerts): void
    {
        try {
            $users = User::where('is_active', true)->get();

            foreach ($users as $user) {
                $variables = [
                    'user_name' => $user->name,
                    'business_name' => config('app.business_name'),
                    'date' => Carbon::now()->format('Y-m-d'),
                    'alerts' => $alerts,
                    'total_alerts' => array_sum(array_map(fn($items) => $items->count(), $alerts))
                ];

                $template = $templateService->getTemplate('stock_alert_summary', $user->preferred_language ?? 'en');
                $message = $templateService->processTemplate($template, $variables);

                // Send email notification
                if ($user->email) {
                    $this->sendEmailAlert($user, 'Stock Alert Summary', $message, $alerts);
                }

                Log::info('Stock alert summary sent', [
                    'user_id' => $user->id,
                    'alert_types' => array_keys($alerts)
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send consolidated stock alert', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send individual item alert
     */
    private function sendIndividualAlert(MessageTemplateService $templateService, InventoryItem $item, string $alertType): void
    {
        try {
            $users = User::where('is_active', true)->get();

            foreach ($users as $user) {
                $variables = [
                    'user_name' => $user->name,
                    'item_name' => $item->name,
                    'item_sku' => $item->sku,
                    'current_quantity' => $item->quantity,
                    'min_stock_level' => $item->minimum_stock,
                    'location' => $item->location->name ?? 'Unknown',
                    'category' => $item->category->name ?? 'Unknown',
                    'expiry_date' => $item->expiry_date ? Carbon::parse($item->expiry_date)->format('Y-m-d') : null,
                    'business_name' => config('app.business_name')
                ];

                $templateKey = match ($alertType) {
                    'low_stock' => 'low_stock_alert',
                    'out_of_stock' => 'out_of_stock_alert',
                    'expiring' => 'expiring_item_alert',
                    default => 'stock_alert'
                };

                $template = $templateService->getTemplate($templateKey, $user->preferred_language ?? 'en');
                $message = $templateService->processTemplate($template, $variables);

                // Send email notification
                if ($user->email) {
                    $subject = match ($alertType) {
                        'low_stock' => 'Low Stock Alert',
                        'out_of_stock' => 'Out of Stock Alert',
                        'expiring' => 'Item Expiring Soon',
                        default => 'Stock Alert'
                    };

                    $this->sendEmailAlert($user, $subject, $message, ['item' => $item, 'type' => $alertType]);
                }

                Log::info('Individual stock alert sent', [
                    'user_id' => $user->id,
                    'item_id' => $item->id,
                    'alert_type' => $alertType
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send individual stock alert', [
                'item_id' => $item->id,
                'alert_type' => $alertType,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send email alert
     */
    private function sendEmailAlert(User $user, string $subject, string $message, array $data = []): void
    {
        try {
            Mail::send([], [], function ($mail) use ($user, $subject, $message, $data) {
                $mail->to($user->email, $user->name)
                    ->subject($subject)
                    ->html($message);
            });

        } catch (\Exception $e) {
            Log::error('Failed to send email alert', [
                'user_id' => $user->id,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendStockAlertJob failed', [
            'alert_type' => $this->alertType,
            'inventory_item_id' => $this->inventoryItemId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
