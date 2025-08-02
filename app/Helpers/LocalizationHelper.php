<?php

namespace App\Helpers;

use App\Services\LocalizationService;
use App\Services\CalendarService;
use App\Services\NumberService;

class LocalizationHelper
{
    /**
     * Get localization service instance
     *
     * @return LocalizationService
     */
    public static function localization(): LocalizationService
    {
        return app(LocalizationService::class);
    }

    /**
     * Get calendar service instance
     *
     * @return CalendarService
     */
    public static function calendar(): CalendarService
    {
        return app(CalendarService::class);
    }

    /**
     * Get number service instance
     *
     * @return NumberService
     */
    public static function number(): NumberService
    {
        return app(NumberService::class);
    }

    /**
     * Quick translate function
     *
     * @param string $key
     * @param array $replace
     * @param string|null $locale
     * @return string
     */
    public static function trans(string $key, array $replace = [], ?string $locale = null): string
    {
        return self::localization()->translate($key, $replace, $locale);
    }

    /**
     * Quick format number function
     *
     * @param int|float $number
     * @param string|null $locale
     * @param int $decimals
     * @return string
     */
    public static function formatNumber($number, ?string $locale = null, int $decimals = 0): string
    {
        $locale = $locale ?? self::localization()->getCurrentLocale();
        return self::number()->formatNumber($number, $locale, $decimals);
    }

    /**
     * Quick format currency function
     *
     * @param float $amount
     * @param string|null $locale
     * @param string $currency
     * @return string
     */
    public static function formatCurrency(float $amount, ?string $locale = null, string $currency = 'USD'): string
    {
        $locale = $locale ?? self::localization()->getCurrentLocale();
        return self::number()->formatCurrency($amount, $locale, $currency);
    }

    /**
     * Quick format date function
     *
     * @param string|\DateTime|\Carbon\Carbon $date
     * @param string|null $locale
     * @param string $format
     * @return string
     */
    public static function formatDate($date, ?string $locale = null, string $format = 'Y/m/d'): string
    {
        $locale = $locale ?? self::localization()->getCurrentLocale();
        return self::calendar()->formatDate($date, $locale, $format);
    }

    /**
     * Check if current locale is RTL
     *
     * @return bool
     */
    public static function isRTL(): bool
    {
        return self::localization()->isRTL();
    }

    /**
     * Get current locale direction
     *
     * @return string
     */
    public static function getDirection(): string
    {
        return self::localization()->getDirection();
    }
}