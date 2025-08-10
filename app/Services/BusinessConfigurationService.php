<?php

namespace App\Services;

use App\Models\BusinessConfiguration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class BusinessConfigurationService
{
    private const CACHE_PREFIX = 'business_config_';
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Get configuration value with caching
     */
    public function get(string $key, $default = null)
    {
        $cacheKey = self::CACHE_PREFIX . $key;
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key, $default) {
            return BusinessConfiguration::getValue($key, $default);
        });
    }

    /**
     * Set configuration value and clear cache
     */
    public function set(string $key, $value, string $type = 'string', string $category = 'general', bool $isEncrypted = false)
    {
        $config = BusinessConfiguration::setValue($key, $value, $type, $category, $isEncrypted);
        
        // Clear cache
        Cache::forget(self::CACHE_PREFIX . $key);
        
        return $config;
    }

    /**
     * Get all configurations by category
     */
    public function getByCategory(string $category): array
    {
        $cacheKey = self::CACHE_PREFIX . 'category_' . $category;
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($category) {
            return BusinessConfiguration::category($category)
                ->get()
                ->mapWithKeys(function ($config) {
                    return [$config->key => $config->is_encrypted ? $config->decrypted_value : $config->value];
                })
                ->toArray();
        });
    }

    /**
     * Update multiple configurations
     */
    public function updateMultiple(array $configurations): void
    {
        foreach ($configurations as $key => $data) {
            $this->set(
                $key,
                $data['value'],
                $data['type'] ?? 'string',
                $data['category'] ?? 'general',
                $data['is_encrypted'] ?? false
            );
        }
    }

    /**
     * Get business information
     */
    public function getBusinessInfo(): array
    {
        return [
            'name' => $this->get('business_name', ''),
            'address' => $this->get('business_address', ''),
            'phone' => $this->get('business_phone', ''),
            'email' => $this->get('business_email', ''),
            'tax_number' => $this->get('business_tax_number', ''),
            'logo' => $this->get('business_logo', ''),
            'currency' => $this->get('business_currency', 'USD'),
            'timezone' => $this->get('business_timezone', 'UTC'),
        ];
    }

    /**
     * Update business information
     */
    public function updateBusinessInfo(array $data): void
    {
        $businessFields = [
            'business_name' => ['value' => $data['name'] ?? '', 'category' => 'business'],
            'business_address' => ['value' => $data['address'] ?? '', 'category' => 'business'],
            'business_phone' => ['value' => $data['phone'] ?? '', 'category' => 'business'],
            'business_email' => ['value' => $data['email'] ?? '', 'category' => 'business'],
            'business_tax_number' => ['value' => $data['tax_number'] ?? '', 'category' => 'business'],
            'business_logo' => ['value' => $data['logo'] ?? '', 'category' => 'business'],
            'business_currency' => ['value' => $data['currency'] ?? 'USD', 'category' => 'business'],
            'business_timezone' => ['value' => $data['timezone'] ?? 'UTC', 'category' => 'business'],
        ];

        $this->updateMultiple($businessFields);
    }

    /**
     * Get tax configuration
     */
    public function getTaxConfig(): array
    {
        return [
            'default_tax_rate' => $this->get('default_tax_rate', 0),
            'tax_inclusive' => $this->get('tax_inclusive', false),
            'tax_number_required' => $this->get('tax_number_required', false),
        ];
    }

    /**
     * Update tax configuration
     */
    public function updateTaxConfig(array $data): void
    {
        $taxFields = [
            'default_tax_rate' => ['value' => $data['default_tax_rate'] ?? 0, 'type' => 'number', 'category' => 'tax'],
            'tax_inclusive' => ['value' => $data['tax_inclusive'] ?? false, 'type' => 'boolean', 'category' => 'tax'],
            'tax_number_required' => ['value' => $data['tax_number_required'] ?? false, 'type' => 'boolean', 'category' => 'tax'],
        ];

        $this->updateMultiple($taxFields);
    }

    /**
     * Get profit configuration
     */
    public function getProfitConfig(): array
    {
        return [
            'default_profit_margin' => $this->get('default_profit_margin', 20),
            'gold_profit_margin' => $this->get('gold_profit_margin', 15),
            'jewelry_profit_margin' => $this->get('jewelry_profit_margin', 25),
        ];
    }

    /**
     * Update profit configuration
     */
    public function updateProfitConfig(array $data): void
    {
        $profitFields = [
            'default_profit_margin' => ['value' => $data['default_profit_margin'] ?? 20, 'type' => 'number', 'category' => 'profit'],
            'gold_profit_margin' => ['value' => $data['gold_profit_margin'] ?? 15, 'type' => 'number', 'category' => 'profit'],
            'jewelry_profit_margin' => ['value' => $data['jewelry_profit_margin'] ?? 25, 'type' => 'number', 'category' => 'profit'],
        ];

        $this->updateMultiple($profitFields);
    }

    /**
     * Upload and save logo
     */
    public function uploadLogo($file): string
    {
        $path = $file->store('logos', 'public');
        $this->set('business_logo', $path, 'string', 'business');
        
        return $path;
    }

    /**
     * Get default pricing percentages for gold pricing calculations
     */
    public function getDefaultPricingPercentages(): array
    {
        return [
            'labor_percentage' => $this->get('default_labor_percentage', 10.0),
            'profit_percentage' => $this->get('default_profit_percentage', 15.0),
            'tax_percentage' => $this->get('default_tax_percentage', 9.0)
        ];
    }

    /**
     * Update default pricing percentages
     */
    public function updateDefaultPricingPercentages(array $data): void
    {
        $pricingFields = [
            'default_labor_percentage' => [
                'value' => $data['labor_percentage'] ?? 10.0,
                'type' => 'number',
                'category' => 'pricing'
            ],
            'default_profit_percentage' => [
                'value' => $data['profit_percentage'] ?? 15.0,
                'type' => 'number',
                'category' => 'pricing'
            ],
            'default_tax_percentage' => [
                'value' => $data['tax_percentage'] ?? 9.0,
                'type' => 'number',
                'category' => 'pricing'
            ]
        ];

        $this->updateMultiple($pricingFields);
    }

    /**
     * Get all business configuration as a flat array
     */
    public function getAllConfigurations(): array
    {
        $cacheKey = self::CACHE_PREFIX . 'all_configurations';
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            return BusinessConfiguration::getAllConfigurations();
        });
    }

    /**
     * Clear all configuration cache
     */
    public function clearCache(): void
    {
        $keys = BusinessConfiguration::pluck('key');
        
        foreach ($keys as $key) {
            Cache::forget(self::CACHE_PREFIX . $key);
        }
        
        // Clear category caches
        $categories = BusinessConfiguration::distinct('category')->pluck('category');
        foreach ($categories as $category) {
            Cache::forget(self::CACHE_PREFIX . 'category_' . $category);
        }
        
        // Clear all configurations cache
        Cache::forget(self::CACHE_PREFIX . 'all_configurations');
    }
}