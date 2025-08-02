<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class LocalizationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_switch_language()
    {
        $response = $this->postJson('/api/localization/switch-language', [
            'locale' => 'fa'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Language switched successfully',
                    'data' => [
                        'locale' => 'fa',
                        'direction' => 'rtl',
                        'name' => 'فارسی'
                    ]
                ]);
    }

    public function test_cannot_switch_to_invalid_language()
    {
        $response = $this->postJson('/api/localization/switch-language', [
            'locale' => 'invalid'
        ]);

        $response->assertStatus(422);
    }

    public function test_can_get_current_locale()
    {
        // First switch the language, then get current locale
        $this->postJson('/api/localization/switch-language', ['locale' => 'fa']);

        $response = $this->getJson('/api/localization/current');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'locale' => 'fa',
                        'direction' => 'rtl',
                        'name' => 'فارسی',
                        'is_rtl' => true
                    ]
                ]);
    }

    public function test_can_get_supported_locales()
    {
        $response = $this->getJson('/api/localization/supported');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'code',
                            'name',
                            'direction'
                        ]
                    ]
                ]);
    }

    public function test_can_get_translations()
    {
        $response = $this->getJson('/api/localization/translations?locale=en');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'locale',
                        'translations'
                    ]
                ]);
    }

    public function test_cannot_get_translations_for_invalid_locale()
    {
        $response = $this->getJson('/api/localization/translations?locale=invalid');

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'Invalid locale provided'
                ]);
    }

    public function test_can_get_calendar_info()
    {
        $response = $this->getJson('/api/localization/calendar?type=gregorian');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'type',
                        'calendar' => [
                            'year',
                            'month',
                            'day',
                            'formatted'
                        ]
                    ]
                ]);
    }

    public function test_can_convert_date()
    {
        $response = $this->postJson('/api/localization/convert-date', [
            'date' => '2024-01-01',
            'from' => 'gregorian',
            'to' => 'jalali'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'original',
                        'converted',
                        'from',
                        'to'
                    ]
                ]);
    }

    public function test_cannot_convert_invalid_date()
    {
        $response = $this->postJson('/api/localization/convert-date', [
            'date' => 'invalid-date',
            'from' => 'gregorian',
            'to' => 'jalali'
        ]);

        $response->assertStatus(400);
    }

    public function test_can_format_number()
    {
        $response = $this->postJson('/api/localization/format-number', [
            'number' => 1234.56,
            'locale' => 'en',
            'type' => 'number',
            'decimals' => 2
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'original',
                        'formatted',
                        'locale',
                        'type'
                    ]
                ]);
    }

    public function test_can_format_currency()
    {
        $response = $this->postJson('/api/localization/format-number', [
            'number' => 1234.56,
            'locale' => 'en',
            'type' => 'currency',
            'currency' => 'USD'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'original' => 1234.56,
                        'locale' => 'en',
                        'type' => 'currency'
                    ]
                ]);
    }

    public function test_can_convert_number_to_words()
    {
        $response = $this->postJson('/api/localization/number-to-words', [
            'number' => 123,
            'locale' => 'fa'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'number',
                        'words',
                        'locale'
                    ]
                ]);
    }

    public function test_requires_valid_number_for_formatting()
    {
        $response = $this->postJson('/api/localization/format-number', [
            'number' => 'not-a-number',
            'locale' => 'en'
        ]);

        $response->assertStatus(422);
    }

    public function test_requires_valid_integer_for_words()
    {
        $response = $this->postJson('/api/localization/number-to-words', [
            'number' => 123.45,
            'locale' => 'fa'
        ]);

        $response->assertStatus(422);
    }
}