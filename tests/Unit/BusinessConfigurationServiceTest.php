<?php

namespace Tests\Unit;

use App\Models\BusinessConfiguration;
use App\Services\BusinessConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BusinessConfigurationServiceTest extends TestCase
{
    use RefreshDatabase;

    private BusinessConfigurationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BusinessConfigurationService();
    }

    public function test_can_get_configuration_value()
    {
        BusinessConfiguration::create([
            'key' => 'test_key',
            'value' => 'test_value',
            'type' => 'string',
            'category' => 'general'
        ]);

        $value = $this->service->get('test_key');
        $this->assertEquals('test_value', $value);
    }

    public function test_returns_default_when_key_not_found()
    {
        $value = $this->service->get('non_existent_key', 'default_value');
        $this->assertEquals('default_value', $value);
    }

    public function test_can_set_configuration_value()
    {
        $config = $this->service->set('new_key', 'new_value', 'string', 'test');

        $this->assertInstanceOf(BusinessConfiguration::class, $config);
        $this->assertEquals('new_key', $config->key);
        $this->assertEquals('new_value', $config->value);
        $this->assertEquals('test', $config->category);
    }

    public function test_can_get_configurations_by_category()
    {
        BusinessConfiguration::create([
            'key' => 'business_name',
            'value' => 'Test Business',
            'category' => 'business'
        ]);

        BusinessConfiguration::create([
            'key' => 'business_email',
            'value' => 'test@business.com',
            'category' => 'business'
        ]);

        $configs = $this->service->getByCategory('business');

        $this->assertArrayHasKey('business_name', $configs);
        $this->assertArrayHasKey('business_email', $configs);
        $this->assertEquals('Test Business', $configs['business_name']);
        $this->assertEquals('test@business.com', $configs['business_email']);
    }

    public function test_can_update_business_info()
    {
        $data = [
            'name' => 'My Jewelry Store',
            'address' => '123 Main St',
            'phone' => '+1234567890',
            'email' => 'info@jewelry.com',
            'currency' => 'USD',
            'timezone' => 'UTC'
        ];

        $this->service->updateBusinessInfo($data);

        $businessInfo = $this->service->getBusinessInfo();
        $this->assertEquals('My Jewelry Store', $businessInfo['name']);
        $this->assertEquals('123 Main St', $businessInfo['address']);
        $this->assertEquals('+1234567890', $businessInfo['phone']);
    }

    public function test_can_update_tax_config()
    {
        $data = [
            'default_tax_rate' => 10.5,
            'tax_inclusive' => true,
            'tax_number_required' => false
        ];

        $this->service->updateTaxConfig($data);

        $taxConfig = $this->service->getTaxConfig();
        $this->assertEquals(10.5, $taxConfig['default_tax_rate']);
        $this->assertTrue($taxConfig['tax_inclusive']);
        $this->assertFalse($taxConfig['tax_number_required']);
    }

    public function test_can_update_profit_config()
    {
        $data = [
            'default_profit_margin' => 25,
            'gold_profit_margin' => 20,
            'jewelry_profit_margin' => 30
        ];

        $this->service->updateProfitConfig($data);

        $profitConfig = $this->service->getProfitConfig();
        $this->assertEquals(25, $profitConfig['default_profit_margin']);
        $this->assertEquals(20, $profitConfig['gold_profit_margin']);
        $this->assertEquals(30, $profitConfig['jewelry_profit_margin']);
    }

    public function test_clears_cache_when_setting_value()
    {
        Cache::shouldReceive('remember')->once()->andReturn('cached_value');
        Cache::shouldReceive('forget')->once();

        $this->service->get('test_key');
        $this->service->set('test_key', 'new_value');
    }

    public function test_can_update_multiple_configurations()
    {
        $configurations = [
            'key1' => ['value' => 'value1', 'type' => 'string', 'category' => 'test'],
            'key2' => ['value' => 'value2', 'type' => 'string', 'category' => 'test'],
        ];

        $this->service->updateMultiple($configurations);

        $this->assertEquals('value1', $this->service->get('key1'));
        $this->assertEquals('value2', $this->service->get('key2'));
    }
}