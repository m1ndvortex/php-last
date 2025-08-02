<?php

namespace App\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Lang;

class LocalizationService
{
    /**
     * Supported locales
     */
    const SUPPORTED_LOCALES = ['en', 'fa'];
    
    /**
     * Default locale
     */
    const DEFAULT_LOCALE = 'en';

    /**
     * Switch application language
     *
     * @param string $locale
     * @return bool
     */
    public function switchLanguage(string $locale): bool
    {
        if (!$this->isValidLocale($locale)) {
            return false;
        }

        App::setLocale($locale);
        Session::put('locale', $locale);
        
        return true;
    }

    /**
     * Get current locale
     *
     * @return string
     */
    public function getCurrentLocale(): string
    {
        return App::getLocale();
    }

    /**
     * Get all translations for a specific locale
     *
     * @param string $locale
     * @return array
     */
    public function getTranslations(string $locale): array
    {
        if (!$this->isValidLocale($locale)) {
            return [];
        }

        $translations = [];
        $langPath = resource_path("lang/{$locale}");
        
        if (!is_dir($langPath)) {
            return [];
        }

        $files = glob($langPath . '/*.php');
        
        foreach ($files as $file) {
            $key = basename($file, '.php');
            $translations[$key] = include $file;
        }

        return $translations;
    }

    /**
     * Get translation for a specific key
     *
     * @param string $key
     * @param array $replace
     * @param string|null $locale
     * @return string
     */
    public function translate(string $key, array $replace = [], ?string $locale = null): string
    {
        if ($locale && $this->isValidLocale($locale)) {
            return Lang::get($key, $replace, $locale);
        }

        return Lang::get($key, $replace);
    }

    /**
     * Check if locale is valid
     *
     * @param string $locale
     * @return bool
     */
    public function isValidLocale(string $locale): bool
    {
        return in_array($locale, self::SUPPORTED_LOCALES);
    }

    /**
     * Get supported locales
     *
     * @return array
     */
    public function getSupportedLocales(): array
    {
        return self::SUPPORTED_LOCALES;
    }

    /**
     * Check if current locale is RTL
     *
     * @return bool
     */
    public function isRTL(): bool
    {
        return $this->getCurrentLocale() === 'fa';
    }

    /**
     * Get locale direction
     *
     * @param string|null $locale
     * @return string
     */
    public function getDirection(?string $locale = null): string
    {
        $locale = $locale ?? $this->getCurrentLocale();
        return $locale === 'fa' ? 'rtl' : 'ltr';
    }

    /**
     * Get locale name
     *
     * @param string $locale
     * @return string
     */
    public function getLocaleName(string $locale): string
    {
        $names = [
            'en' => 'English',
            'fa' => 'فارسی'
        ];

        return $names[$locale] ?? $locale;
    }
}