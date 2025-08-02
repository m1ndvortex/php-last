<?php

namespace App\Services;

class NumberService
{
    /**
     * Persian numerals mapping
     */
    const PERSIAN_NUMERALS = [
        '0' => '۰',
        '1' => '۱',
        '2' => '۲',
        '3' => '۳',
        '4' => '۴',
        '5' => '۵',
        '6' => '۶',
        '7' => '۷',
        '8' => '۸',
        '9' => '۹'
    ];

    /**
     * English numerals mapping (reverse of Persian)
     */
    const ENGLISH_NUMERALS = [
        '۰' => '0',
        '۱' => '1',
        '۲' => '2',
        '۳' => '3',
        '۴' => '4',
        '۵' => '5',
        '۶' => '6',
        '۷' => '7',
        '۸' => '8',
        '۹' => '9'
    ];

    /**
     * Persian number words
     */
    const PERSIAN_NUMBER_WORDS = [
        0 => 'صفر',
        1 => 'یک',
        2 => 'دو',
        3 => 'سه',
        4 => 'چهار',
        5 => 'پنج',
        6 => 'شش',
        7 => 'هفت',
        8 => 'هشت',
        9 => 'نه',
        10 => 'ده',
        11 => 'یازده',
        12 => 'دوازده',
        13 => 'سیزده',
        14 => 'چهارده',
        15 => 'پانزده',
        16 => 'شانزده',
        17 => 'هفده',
        18 => 'هجده',
        19 => 'نوزده',
        20 => 'بیست',
        30 => 'سی',
        40 => 'چهل',
        50 => 'پنجاه',
        60 => 'شصت',
        70 => 'هفتاد',
        80 => 'هشتاد',
        90 => 'نود',
        100 => 'یکصد',
        200 => 'دویست',
        300 => 'سیصد',
        400 => 'چهارصد',
        500 => 'پانصد',
        600 => 'ششصد',
        700 => 'هفتصد',
        800 => 'هشتصد',
        900 => 'نهصد',
        1000 => 'هزار',
        1000000 => 'میلیون',
        1000000000 => 'میلیارد'
    ];

    /**
     * Convert English numerals to Persian
     *
     * @param string|int|float $input
     * @return string
     */
    public function toPersianNumerals($input): string
    {
        $input = (string) $input;
        
        return strtr($input, self::PERSIAN_NUMERALS);
    }

    /**
     * Convert Persian numerals to English
     *
     * @param string $input
     * @return string
     */
    public function toEnglishNumerals(string $input): string
    {
        return strtr($input, self::ENGLISH_NUMERALS);
    }

    /**
     * Format number according to locale
     *
     * @param int|float $number
     * @param string $locale
     * @param int $decimals
     * @param string $decimalSeparator
     * @param string $thousandsSeparator
     * @return string
     */
    public function formatNumber(
        $number, 
        string $locale = 'en', 
        int $decimals = 0, 
        string $decimalSeparator = null, 
        string $thousandsSeparator = null
    ): string {
        if ($locale === 'fa') {
            $decimalSeparator = $decimalSeparator ?? '/';
            $thousandsSeparator = $thousandsSeparator ?? '،';
        } else {
            $decimalSeparator = $decimalSeparator ?? '.';
            $thousandsSeparator = $thousandsSeparator ?? ',';
        }

        $formatted = number_format($number, $decimals, $decimalSeparator, $thousandsSeparator);

        if ($locale === 'fa') {
            $formatted = $this->toPersianNumerals($formatted);
        }

        return $formatted;
    }

    /**
     * Format currency according to locale
     *
     * @param float $amount
     * @param string $locale
     * @param string $currency
     * @return string
     */
    public function formatCurrency(float $amount, string $locale = 'en', string $currency = 'USD'): string
    {
        if ($locale === 'fa') {
            $formatted = $this->formatNumber($amount, 'fa', 2);
            
            switch ($currency) {
                case 'IRR':
                case 'IRT':
                    return $formatted . ' ریال';
                case 'USD':
                    return '$' . $formatted;
                case 'EUR':
                    return '€' . $formatted;
                default:
                    return $formatted . ' ' . $currency;
            }
        }

        $formatted = $this->formatNumber($amount, 'en', 2);
        
        switch ($currency) {
            case 'USD':
                return '$' . $formatted;
            case 'EUR':
                return '€' . $formatted;
            case 'IRR':
            case 'IRT':
                return $formatted . ' IRR';
            default:
                return $formatted . ' ' . $currency;
        }
    }

    /**
     * Convert number to Persian words
     *
     * @param int $number
     * @return string
     */
    public function numberToPersianWords(int $number): string
    {
        if ($number == 0) {
            return self::PERSIAN_NUMBER_WORDS[0];
        }

        if ($number < 0) {
            return 'منفی ' . $this->numberToPersianWords(abs($number));
        }

        $result = '';

        // Billions
        if ($number >= 1000000000) {
            $billions = intval($number / 1000000000);
            $result .= $this->convertHundreds($billions) . ' میلیارد ';
            $number %= 1000000000;
        }

        // Millions
        if ($number >= 1000000) {
            $millions = intval($number / 1000000);
            $result .= $this->convertHundreds($millions) . ' میلیون ';
            $number %= 1000000;
        }

        // Thousands
        if ($number >= 1000) {
            $thousands = intval($number / 1000);
            $result .= $this->convertHundreds($thousands) . ' هزار ';
            $number %= 1000;
        }

        // Hundreds
        if ($number > 0) {
            $result .= $this->convertHundreds($number);
        }

        return trim($result);
    }

    /**
     * Convert hundreds to Persian words
     *
     * @param int $number
     * @return string
     */
    private function convertHundreds(int $number): string
    {
        $result = '';

        if ($number >= 100) {
            $hundreds = intval($number / 100);
            $result .= self::PERSIAN_NUMBER_WORDS[$hundreds * 100] . ' ';
            $number %= 100;
        }

        if ($number >= 20) {
            $tens = intval($number / 10) * 10;
            $result .= self::PERSIAN_NUMBER_WORDS[$tens];
            $number %= 10;
            
            if ($number > 0) {
                $result .= ' و ' . self::PERSIAN_NUMBER_WORDS[$number];
            }
        } elseif ($number > 0) {
            $result .= self::PERSIAN_NUMBER_WORDS[$number];
        }

        return trim($result);
    }

    /**
     * Parse Persian number input to numeric value
     *
     * @param string $input
     * @return float|int|null
     */
    public function parsePersianNumber(string $input)
    {
        // Convert Persian numerals to English first
        $normalized = $this->toEnglishNumerals($input);
        
        // Replace Persian decimal separator
        $normalized = str_replace('/', '.', $normalized);
        
        // Remove Persian thousands separator
        $normalized = str_replace('،', '', $normalized);
        
        // Check if it's a valid number
        if (is_numeric($normalized)) {
            return strpos($normalized, '.') !== false ? (float) $normalized : (int) $normalized;
        }
        
        return null;
    }

    /**
     * Get ordinal number in Persian
     *
     * @param int $number
     * @return string
     */
    public function getPersianOrdinal(int $number): string
    {
        $word = $this->numberToPersianWords($number);
        
        // Add Persian ordinal suffix
        return $word . 'م';
    }

    /**
     * Format percentage according to locale
     *
     * @param float $percentage
     * @param string $locale
     * @param int $decimals
     * @return string
     */
    public function formatPercentage(float $percentage, string $locale = 'en', int $decimals = 1): string
    {
        $formatted = $this->formatNumber($percentage, $locale, $decimals);
        
        if ($locale === 'fa') {
            return $formatted . '٪';
        }
        
        return $formatted . '%';
    }
}