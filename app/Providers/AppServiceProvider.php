<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\Category;
use App\Models\Account;
use App\Models\Location;
use App\Models\Transaction;
use App\Observers\CacheInvalidationObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register cache invalidation observers for core models
        Customer::observe(CacheInvalidationObserver::class);
        InventoryItem::observe(CacheInvalidationObserver::class);
        Invoice::observe(CacheInvalidationObserver::class);
        Category::observe(CacheInvalidationObserver::class);
        Account::observe(CacheInvalidationObserver::class);
        Location::observe(CacheInvalidationObserver::class);
        Transaction::observe(CacheInvalidationObserver::class);
    }
}