<?php

namespace Tests\Unit;

use App\Services\LocalizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class LocalizationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LocalizationService $localizationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->localizationService = new LocalizationService();
    }

    public function test_can_switch_to_valid_language()
    {
        $result = $this->localizationService->switchLanguage('fa');
        
        $this->assertTrue($result);
        $this->assertEquals('fa', App::getLocale());
        $this->assertEquals('fa', Session::get('locale'));
    }

    public function test_cannot_switch_to_invalid_language()
    {
        $result = $this->localizationService->switchLanguage('invalid');
        
        $this->assertFalse($result);
        $this->assertNotEquals('invalid', App::getLocale());
    }

    public function test_can_get_current_locale()
    {
        App::setLocale('fa');
        
        $locale = $this->localizationService->getCurrentLocale();
        
        $this->assertEquals('fa', $locale);
    }

    public function test_can_validate_locale()
    {
        $this->assertTrue($this->localizationService->isValidLocale('en'));
        $this->assertTrue($this->localizationService->isValidLocale('fa'));
        $this->assertFalse($this->localizationService->isValidLocale('invalid'));
        $this->assertFalse($this->localizationService->isValidLocale(''));
    }

    public function test_can_get_supported_locales()
    {
        $locales = $this->localizationService->getSupportedLocales();
        
        $this->assertIsArray($locales);
        $this->assertContains('en', $locales);
        $this->assertContains('fa', $locales);
    }

    public function test_can_detect_rtl_language()
    {
        App::setLocale('fa');
        $this->assertTrue($this->localizationService->isRTL());
        
        App::setLocale('en');
        $this->assertFalse($this->localizationService->isRTL());
    }

    public function test_can_get_direction()
    {
        $this->assertEquals('rtl', $this->localizationService->getDirection('fa'));
        $this->assertEquals('ltr', $this->localizationService->getDirection('en'));
    }

    public function test_can_get_locale_name()
    {
        $this->assertEquals('English', $this->localizationService->getLocaleName('en'));
        $this->assertEquals('فارسی', $this->localizationService->getLocaleName('fa'));
    }

    public function test_can_get_translations()
    {
        // Create a test translation file
        $langPath = resource_path('lang/en');
        if (!is_dir($langPath)) {
            mkdir($langPath, 0755, true);
        }
        
        file_put_contents($langPath . '/test.php', "<?php\nreturn ['key' => 'value'];");
        
        $translations = $this->localizationService->getTranslations('en');
        
        $this->assertIsArray($translations);
        
        // Clean up
        unlink($langPath . '/test.php');
    }

    public function test_returns_empty_array_for_invalid_locale_translations()
    {
        $translations = $this->localizationService->getTranslations('invalid');
        
        $this->assertIsArray($translations);
        $this->assertEmpty($translations);
    }

    public function test_can_translate_key()
    {
        $translation = $this->localizationService->translate('auth.login');
        
        $this->assertIsString($translation);
    }
}