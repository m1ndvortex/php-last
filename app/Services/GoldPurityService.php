<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class GoldPurityService
{
    /**
     * Standard gold purity options with their karat equivalents.
     */
    private const STANDARD_PURITIES = [
        ['karat' => 24, 'purity' => 24.0, 'percentage' => 99.9],
        ['karat' => 22, 'purity' => 22.0, 'percentage' => 91.7],
        ['karat' => 21, 'purity' => 21.0, 'percentage' => 87.5],
        ['karat' => 18, 'purity' => 18.0, 'percentage' => 75.0],
        ['karat' => 14, 'purity' => 14.0, 'percentage' => 58.3],
        ['karat' => 10, 'purity' => 10.0, 'percentage' => 41.7],
        ['karat' => 9, 'purity' => 9.0, 'percentage' => 37.5],
    ];

    /**
     * Persian numerals mapping.
     */
    private const PERSIAN_NUMERALS = [
        '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
        '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
        '.' => '٫'
    ];

    /**
     * Get standard gold purity options.
     */
    public function getStandardPurities(): array
    {
        $locale = app()->getLocale();
        
        return array_map(function ($purity) use ($locale) {
            return [
                'karat' => $purity['karat'],
                'purity' => $purity['purity'],
                'percentage' => $purity['percentage'],
                'display' => $this->formatPurityDisplay($purity['purity'], $locale),
                'display_name' => $this->formatPurityDisplay($purity['purity'], $locale),
                'label' => $this->getPurityLabel($purity['purity'], $locale),
            ];
        }, self::STANDARD_PURITIES);
    }

    /**
     * Format gold purity for display based on locale.
     */
    public function formatPurityDisplay(float $purity, string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        
        if ($locale === 'fa') {
            return $this->formatPersianPurity($purity);
        }
        
        return $this->formatEnglishPurity($purity);
    }

    /**
     * Format purity in Persian with Persian numerals.
     */
    private function formatPersianPurity(float $purity): string
    {
        $formattedNumber = number_format($purity, 1);
        $persianNumber = $this->convertToPersianNumerals($formattedNumber);
        
        return $persianNumber . ' عیار';
    }

    /**
     * Format purity in English.
     */
    private function formatEnglishPurity(float $purity): string
    {
        return number_format($purity, 1) . 'K';
    }

    /**
     * Convert English numerals to Persian numerals.
     */
    public function convertToPersianNumerals(string $text): string
    {
        return strtr($text, self::PERSIAN_NUMERALS);
    }

    /**
     * Convert Persian numerals to English numerals.
     */
    public function convertToEnglishNumerals(string $text): string
    {
        return strtr($text, array_flip(self::PERSIAN_NUMERALS));
    }

    /**
     * Get purity label with additional information.
     */
    public function getPurityLabel(float $purity, string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $percentage = $this->convertKaratToPercentage($purity);
        
        if ($locale === 'fa') {
            $persianPurity = $this->convertToPersianNumerals(number_format($purity, 1));
            $persianPercentage = $this->convertToPersianNumerals(number_format($percentage, 1));
            return "{$persianPurity} عیار ({$persianPercentage}٪ طلا)";
        }
        
        return number_format($purity, 1) . "K (" . number_format($percentage, 1) . "% gold)";
    }

    /**
     * Convert karat to purity (they're the same for our purposes).
     */
    public function convertKaratToPurity(float $karat): float
    {
        return $karat;
    }

    /**
     * Convert purity to karat (they're the same for our purposes).
     */
    public function convertPurityToKarat(float $purity): float
    {
        return $purity;
    }

    /**
     * Convert karat to percentage.
     */
    public function convertKaratToPercentage(float $karat): float
    {
        return ($karat / 24) * 100;
    }

    /**
     * Convert percentage to karat.
     */
    public function convertPercentageToKarat(float $percentage): float
    {
        return ($percentage / 100) * 24;
    }

    /**
     * Validate gold purity value.
     */
    public function validatePurity(float $purity): bool
    {
        return $purity >= 1 && $purity <= 24;
    }

    /**
     * Get purity validation rules for Laravel validation.
     */
    public function getPurityValidationRules(): array
    {
        return [
            'numeric',
            'min:1',
            'max:24',
            function ($attribute, $value, $fail) {
                if (!$this->validatePurity($value)) {
                    $fail('The ' . $attribute . ' must be between 1 and 24 karats.');
                }
            }
        ];
    }

    /**
     * Get purity ranges for filtering.
     */
    public function getPurityRanges(): array
    {
        $locale = app()->getLocale();
        
        $ranges = [
            ['min' => 1, 'max' => 10, 'label_en' => 'Low Purity (1-10K)', 'label_fa' => 'عیار پایین (۱-۱۰)'],
            ['min' => 10, 'max' => 14, 'label_en' => 'Medium Purity (10-14K)', 'label_fa' => 'عیار متوسط (۱۰-۱۴)'],
            ['min' => 14, 'max' => 18, 'label_en' => 'High Purity (14-18K)', 'label_fa' => 'عیار بالا (۱۴-۱۸)'],
            ['min' => 18, 'max' => 22, 'label_en' => 'Very High Purity (18-22K)', 'label_fa' => 'عیار خیلی بالا (۱۸-۲۲)'],
            ['min' => 22, 'max' => 24, 'label_en' => 'Pure Gold (22-24K)', 'label_fa' => 'طلای خالص (۲۲-۲۴)'],
        ];

        return array_map(function ($range) use ($locale) {
            return [
                'min' => $range['min'],
                'max' => $range['max'],
                'label' => $locale === 'fa' ? $range['label_fa'] : $range['label_en'],
                'min_display' => $this->formatPurityDisplay($range['min'], $locale),
                'max_display' => $this->formatPurityDisplay($range['max'], $locale),
            ];
        }, $ranges);
    }

    /**
     * Find the closest standard purity to a given value.
     */
    public function findClosestStandardPurity(float $purity): array
    {
        $closest = null;
        $minDifference = PHP_FLOAT_MAX;

        foreach (self::STANDARD_PURITIES as $standard) {
            $difference = abs($standard['purity'] - $purity);
            if ($difference < $minDifference) {
                $minDifference = $difference;
                $closest = $standard;
            }
        }

        return $closest ?? self::STANDARD_PURITIES[0];
    }

    /**
     * Get purity category (low, medium, high, etc.).
     */
    public function getPurityCategory(float $purity): string
    {
        $ranges = $this->getPurityRanges();
        
        foreach ($ranges as $range) {
            if ($purity >= $range['min'] && $purity <= $range['max']) {
                return $range['label'];
            }
        }

        return 'Unknown';
    }

    /**
     * Format purity for invoice display.
     */
    public function formatForInvoice(float $purity, string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $percentage = $this->convertKaratToPercentage($purity);
        
        if ($locale === 'fa') {
            $persianPurity = $this->convertToPersianNumerals(number_format($purity, 1));
            $persianPercentage = $this->convertToPersianNumerals(number_format($percentage, 1));
            return "عیار {$persianPurity} ({$persianPercentage}٪)";
        }
        
        return number_format($purity, 1) . "K (" . number_format($percentage, 1) . "%)";
    }

    /**
     * Parse purity from user input (handles both English and Persian numerals).
     */
    public function parsePurityInput(string $input): ?float
    {
        // Convert Persian numerals to English
        $normalizedInput = $this->convertToEnglishNumerals($input);
        
        // Remove non-numeric characters except decimal point
        $cleanInput = preg_replace('/[^\d.]/', '', $normalizedInput);
        
        if (empty($cleanInput)) {
            return null;
        }

        $purity = (float) $cleanInput;
        
        return $this->validatePurity($purity) ? $purity : null;
    }

    /**
     * Get purity statistics for a collection of items.
     */
    public function getPurityStatistics(array $purities): array
    {
        if (empty($purities)) {
            return [
                'count' => 0,
                'average' => 0,
                'min' => 0,
                'max' => 0,
                'most_common' => null,
            ];
        }

        $validPurities = array_filter($purities, function ($purity) {
            return $purity !== null && $this->validatePurity($purity);
        });

        if (empty($validPurities)) {
            return [
                'count' => 0,
                'average' => 0,
                'min' => 0,
                'max' => 0,
                'most_common' => null,
            ];
        }

        $count = count($validPurities);
        $average = array_sum($validPurities) / $count;
        $min = min($validPurities);
        $max = max($validPurities);
        
        // Find most common purity
        $purityCounts = array_count_values($validPurities);
        arsort($purityCounts);
        $mostCommon = array_key_first($purityCounts);

        return [
            'count' => $count,
            'average' => round($average, 2),
            'min' => $min,
            'max' => $max,
            'most_common' => $mostCommon,
            'distribution' => $purityCounts,
        ];
    }

    /**
     * Log purity-related operations for audit purposes.
     */
    public function logPurityOperation(string $operation, array $data): void
    {
        Log::info('Gold purity operation', [
            'operation' => $operation,
            'data' => $data,
            'timestamp' => now(),
        ]);
    }
}