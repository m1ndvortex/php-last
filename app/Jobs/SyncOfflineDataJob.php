<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

class SyncOfflineDataJob implements ShouldQueue
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
    public $timeout = 600;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $userId,
        public array $syncData = [],
        public string $syncType = 'full' // 'full', 'incremental', 'specific'
    ) {
        $this->onQueue('sync');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting offline data synchronization', [
                'user_id' => $this->userId,
                'sync_type' => $this->syncType,
                'data_keys' => array_keys($this->syncData)
            ]);

            $user = User::findOrFail($this->userId);

            // Process offline data based on sync type
            match ($this->syncType) {
                'full' => $this->performFullSync($user),
                'incremental' => $this->performIncrementalSync($user),
                'specific' => $this->performSpecificSync($user),
                'upload' => $this->processOfflineUploads($user),
                default => throw new \InvalidArgumentException("Unsupported sync type: {$this->syncType}")
            };

            // Update user's last sync timestamp
            $user->update(['last_sync_at' => now()]);

            Log::info('Offline data synchronization completed', [
                'user_id' => $this->userId,
                'sync_type' => $this->syncType
            ]);

        } catch (\Exception $e) {
            Log::error('SyncOfflineDataJob failed', [
                'user_id' => $this->userId,
                'sync_type' => $this->syncType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Perform full synchronization
     */
    private function performFullSync(User $user): void
    {
        Log::info('Performing full sync', ['user_id' => $user->id]);

        // Cache essential data for offline use
        $this->cacheCustomersData($user);
        $this->cacheInventoryData($user);
        $this->cacheInvoicesData($user);
        $this->cacheConfigurationData($user);
        $this->cacheTranslationsData($user);

        // Store sync metadata
        $this->storeSyncMetadata($user, 'full');
    }

    /**
     * Perform incremental synchronization
     */
    private function performIncrementalSync(User $user): void
    {
        Log::info('Performing incremental sync', ['user_id' => $user->id]);

        $lastSync = $user->last_sync_at ?? Carbon::now()->subDays(7);

        // Sync only changed data since last sync
        $this->cacheChangedCustomers($user, $lastSync);
        $this->cacheChangedInventory($user, $lastSync);
        $this->cacheChangedInvoices($user, $lastSync);

        // Store sync metadata
        $this->storeSyncMetadata($user, 'incremental');
    }

    /**
     * Perform specific data synchronization
     */
    private function performSpecificSync(User $user): void
    {
        Log::info('Performing specific sync', [
            'user_id' => $user->id,
            'data_types' => array_keys($this->syncData)
        ]);

        foreach ($this->syncData as $dataType => $params) {
            match ($dataType) {
                'customers' => $this->syncSpecificCustomers($user, $params),
                'inventory' => $this->syncSpecificInventory($user, $params),
                'invoices' => $this->syncSpecificInvoices($user, $params),
                default => Log::warning('Unknown sync data type', ['type' => $dataType])
            };
        }

        // Store sync metadata
        $this->storeSyncMetadata($user, 'specific');
    }

    /**
     * Process offline uploads and changes
     */
    private function processOfflineUploads(User $user): void
    {
        Log::info('Processing offline uploads', ['user_id' => $user->id]);

        foreach ($this->syncData as $dataType => $items) {
            match ($dataType) {
                'customers' => $this->processOfflineCustomers($user, $items),
                'invoices' => $this->processOfflineInvoices($user, $items),
                'inventory_movements' => $this->processOfflineInventoryMovements($user, $items),
                default => Log::warning('Unknown upload data type', ['type' => $dataType])
            };
        }
    }

    /**
     * Cache customers data for offline use
     */
    private function cacheCustomersData(User $user): void
    {
        $customers = Customer::with(['communications' => function ($query) {
            $query->latest()->limit(10);
        }])->get();

        $cacheKey = "offline_data:user_{$user->id}:customers";
        Cache::put($cacheKey, $customers->toArray(), now()->addHours(24));

        Log::info('Cached customers data', [
            'user_id' => $user->id,
            'count' => $customers->count()
        ]);
    }

    /**
     * Cache inventory data for offline use
     */
    private function cacheInventoryData(User $user): void
    {
        $inventory = InventoryItem::with(['category', 'location'])
            ->where('quantity', '>', 0)
            ->get();

        $cacheKey = "offline_data:user_{$user->id}:inventory";
        Cache::put($cacheKey, $inventory->toArray(), now()->addHours(24));

        Log::info('Cached inventory data', [
            'user_id' => $user->id,
            'count' => $inventory->count()
        ]);
    }

    /**
     * Cache recent invoices data for offline use
     */
    private function cacheInvoicesData(User $user): void
    {
        $invoices = Invoice::with(['customer', 'items'])
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->latest()
            ->limit(100)
            ->get();

        $cacheKey = "offline_data:user_{$user->id}:invoices";
        Cache::put($cacheKey, $invoices->toArray(), now()->addHours(24));

        Log::info('Cached invoices data', [
            'user_id' => $user->id,
            'count' => $invoices->count()
        ]);
    }

    /**
     * Cache configuration data for offline use
     */
    private function cacheConfigurationData(User $user): void
    {
        $config = [
            'business' => [
                'name' => config('app.business_name'),
                'currency' => config('app.business_currency'),
                'timezone' => config('app.business_timezone'),
            ],
            'localization' => [
                'default_locale' => config('app.default_locale'),
                'supported_locales' => explode(',', config('app.supported_locales')),
            ],
            'features' => [
                'two_factor_enabled' => config('app.two_factor_enabled'),
                'backup_enabled' => config('app.backup_enabled'),
            ]
        ];

        $cacheKey = "offline_data:user_{$user->id}:config";
        Cache::put($cacheKey, $config, now()->addHours(24));

        Log::info('Cached configuration data', ['user_id' => $user->id]);
    }

    /**
     * Cache translations data for offline use
     */
    private function cacheTranslationsData(User $user): void
    {
        $supportedLocales = explode(',', config('app.supported_locales', 'en,fa'));
        $translations = [];

        foreach ($supportedLocales as $locale) {
            $translationPath = resource_path("lang/{$locale}");
            if (is_dir($translationPath)) {
                $translations[$locale] = $this->loadTranslationFiles($translationPath);
            }
        }

        $cacheKey = "offline_data:user_{$user->id}:translations";
        Cache::put($cacheKey, $translations, now()->addHours(24));

        Log::info('Cached translations data', [
            'user_id' => $user->id,
            'locales' => array_keys($translations)
        ]);
    }

    /**
     * Load translation files from directory
     */
    private function loadTranslationFiles(string $path): array
    {
        $translations = [];
        $files = glob($path . '/*.php');

        foreach ($files as $file) {
            $key = basename($file, '.php');
            $translations[$key] = include $file;
        }

        return $translations;
    }

    /**
     * Process offline customer changes
     */
    private function processOfflineCustomers(User $user, array $customers): void
    {
        foreach ($customers as $customerData) {
            try {
                if (isset($customerData['offline_id'])) {
                    // New customer created offline
                    unset($customerData['offline_id']);
                    $customer = Customer::create($customerData);
                    
                    Log::info('Created customer from offline data', [
                        'customer_id' => $customer->id,
                        'name' => $customer->name
                    ]);
                } elseif (isset($customerData['id'])) {
                    // Existing customer updated offline
                    $customer = Customer::find($customerData['id']);
                    if ($customer) {
                        $customer->update($customerData);
                        
                        Log::info('Updated customer from offline data', [
                            'customer_id' => $customer->id,
                            'name' => $customer->name
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to process offline customer', [
                    'customer_data' => $customerData,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Store sync metadata
     */
    private function storeSyncMetadata(User $user, string $syncType): void
    {
        $metadata = [
            'user_id' => $user->id,
            'sync_type' => $syncType,
            'synced_at' => now()->toISOString(),
            'version' => config('app.version', '1.0.0')
        ];

        $cacheKey = "offline_data:user_{$user->id}:metadata";
        Cache::put($cacheKey, $metadata, now()->addDays(7));
    }

    /**
     * Cache changed customers since last sync
     */
    private function cacheChangedCustomers(User $user, Carbon $lastSync): void
    {
        $customers = Customer::where('updated_at', '>', $lastSync)
            ->with(['communications' => function ($query) use ($lastSync) {
                $query->where('created_at', '>', $lastSync)->latest()->limit(5);
            }])
            ->get();

        if ($customers->count() > 0) {
            $cacheKey = "offline_data:user_{$user->id}:customers_delta";
            Cache::put($cacheKey, $customers->toArray(), now()->addHours(24));

            Log::info('Cached changed customers', [
                'user_id' => $user->id,
                'count' => $customers->count()
            ]);
        }
    }

    /**
     * Cache changed inventory since last sync
     */
    private function cacheChangedInventory(User $user, Carbon $lastSync): void
    {
        $inventory = InventoryItem::where('updated_at', '>', $lastSync)
            ->with(['category', 'location'])
            ->get();

        if ($inventory->count() > 0) {
            $cacheKey = "offline_data:user_{$user->id}:inventory_delta";
            Cache::put($cacheKey, $inventory->toArray(), now()->addHours(24));

            Log::info('Cached changed inventory', [
                'user_id' => $user->id,
                'count' => $inventory->count()
            ]);
        }
    }

    /**
     * Cache changed invoices since last sync
     */
    private function cacheChangedInvoices(User $user, Carbon $lastSync): void
    {
        $invoices = Invoice::where('updated_at', '>', $lastSync)
            ->with(['customer', 'items'])
            ->get();

        if ($invoices->count() > 0) {
            $cacheKey = "offline_data:user_{$user->id}:invoices_delta";
            Cache::put($cacheKey, $invoices->toArray(), now()->addHours(24));

            Log::info('Cached changed invoices', [
                'user_id' => $user->id,
                'count' => $invoices->count()
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SyncOfflineDataJob failed', [
            'user_id' => $this->userId,
            'sync_type' => $this->syncType,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
