<?php

namespace App\Services;

use Carbon\Carbon;
use DateTime;

class CalendarService
{
    /**
     * Jalali months in Persian
     */
    const JALALI_MONTHS = [
        1 => 'فروردین',
        2 => 'اردیبهشت',
        3 => 'خرداد',
        4 => 'تیر',
        5 => 'مرداد',
        6 => 'شهریور',
        7 => 'مهر',
        8 => 'آبان',
        9 => 'آذر',
        10 => 'دی',
        11 => 'بهمن',
        12 => 'اسفند'
    ];

    /**
     * Jalali weekdays in Persian
     */
    const JALALI_WEEKDAYS = [
        0 => 'یکشنبه',
        1 => 'دوشنبه',
        2 => 'سه‌شنبه',
        3 => 'چهارشنبه',
        4 => 'پنج‌شنبه',
        5 => 'جمعه',
        6 => 'شنبه'
    ];

    /**
     * Convert Gregorian date to Jalali
     *
     * @param string|DateTime|Carbon $date
     * @return array
     */
    public function gregorianToJalali($date): array
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        } elseif ($date instanceof DateTime) {
            $date = Carbon::instance($date);
        }

        $year = $date->year;
        $month = $date->month;
        $day = $date->day;

        $jalali = $this->convertGregorianToJalali($year, $month, $day);

        return [
            'year' => $jalali[0],
            'month' => $jalali[1],
            'day' => $jalali[2],
            'month_name' => self::JALALI_MONTHS[$jalali[1]],
            'weekday' => self::JALALI_WEEKDAYS[$date->dayOfWeek],
            'formatted' => sprintf('%04d/%02d/%02d', $jalali[0], $jalali[1], $jalali[2])
        ];
    }

    /**
     * Convert Jalali date to Gregorian
     *
     * @param int $year
     * @param int $month
     * @param int $day
     * @return Carbon
     */
    public function jalaliToGregorian(int $year, int $month, int $day): Carbon
    {
        $gregorian = $this->convertJalaliToGregorian($year, $month, $day);
        
        return Carbon::createFromDate($gregorian[0], $gregorian[1], $gregorian[2]);
    }

    /**
     * Format date according to locale
     *
     * @param string|DateTime|Carbon $date
     * @param string $locale
     * @param string $format
     * @return string
     */
    public function formatDate($date, string $locale = 'en', string $format = 'Y/m/d'): string
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        } elseif ($date instanceof DateTime) {
            $date = Carbon::instance($date);
        }

        if ($locale === 'fa') {
            $jalali = $this->gregorianToJalali($date);
            
            switch ($format) {
                case 'Y/m/d':
                    return $jalali['formatted'];
                case 'F j, Y':
                    return $jalali['month_name'] . ' ' . $jalali['day'] . '، ' . $jalali['year'];
                case 'l, F j, Y':
                    return $jalali['weekday'] . '، ' . $jalali['month_name'] . ' ' . $jalali['day'] . '، ' . $jalali['year'];
                default:
                    return $jalali['formatted'];
            }
        }

        return $date->format($format);
    }

    /**
     * Get current date in specified calendar
     *
     * @param string $calendar
     * @return array
     */
    public function getCurrentDate(string $calendar = 'gregorian'): array
    {
        $now = Carbon::now();

        if ($calendar === 'jalali') {
            return $this->gregorianToJalali($now);
        }

        return [
            'year' => $now->year,
            'month' => $now->month,
            'day' => $now->day,
            'month_name' => $now->format('F'),
            'weekday' => $now->format('l'),
            'formatted' => $now->format('Y/m/d')
        ];
    }

    /**
     * Core Gregorian to Jalali conversion algorithm
     *
     * @param int $year
     * @param int $month
     * @param int $day
     * @return array
     */
    private function convertGregorianToJalali(int $year, int $month, int $day): array
    {
        $jy = ($year <= 1600) ? 0 : 979;
        $year -= ($year <= 1600) ? 621 : 1600;
        $jp = 0;

        for ($i = 0; $i < $year; $i++) {
            $jp += ($this->isLeapGregorian($i + (($jy == 979) ? 1600 : 621))) ? 366 : 365;
        }

        for ($i = 0; $i < $month - 1; $i++) {
            $jp += $this->getGregorianMonthDays($i + 1, $year + (($jy == 979) ? 1600 : 621));
        }

        $jp += $day;

        $jy += 33 * intval($jp / 12053);
        $jp %= 12053;

        $jy += 4 * intval($jp / 1461);
        $jp %= 1461;

        if ($jp > 365) {
            $jy += intval(($jp - 1) / 365);
            $jp = ($jp - 1) % 365;
        }

        if ($jp < 186) {
            $jm = 1 + intval($jp / 31);
            $jd = 1 + ($jp % 31);
        } else {
            $jm = 7 + intval(($jp - 186) / 30);
            $jd = 1 + (($jp - 186) % 30);
        }

        return [$jy, $jm, $jd];
    }

    /**
     * Core Jalali to Gregorian conversion algorithm
     *
     * @param int $year
     * @param int $month
     * @param int $day
     * @return array
     */
    private function convertJalaliToGregorian(int $year, int $month, int $day): array
    {
        $jy = ($year >= 979) ? 1600 : 621;
        $year -= ($year >= 979) ? 979 : 0;
        $jp = 365 * $year + (intval($year / 33) * 8) + intval((($year % 33) + 3) / 4) + 78 + $day;

        if ($month < 7) {
            $jp += ($month - 1) * 31;
        } else {
            $jp += (($month - 7) * 30) + 186;
        }

        $gy = 400 * intval($jp / 146097);
        $jp %= 146097;

        $leap = true;
        if ($jp >= 36525) {
            $jp--;
            $gy += 100 * intval($jp / 36524);
            $jp %= 36524;
            if ($jp >= 365) {
                $jp++;
                $leap = false;
            }
        }

        $gy += 4 * intval($jp / 1461);
        $jp %= 1461;

        if ($jp >= 366) {
            $leap = false;
            $jp--;
            $gy += intval($jp / 365);
            $jp = $jp % 365;
        }

        if ($jp == 0) {
            return [$jy + $gy, 12, 31];
        }

        $sal_a = [0, 31, (($leap || (($gy % 4 == 0) && (($gy % 100 != 0) || ($gy % 400 == 0)))) ? 29 : 28), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

        $gm = 0;
        while ($gm < 13 && $jp >= $sal_a[$gm]) {
            $jp -= $sal_a[$gm];
            $gm++;
        }

        return [$jy + $gy, $gm, $jp + 1];
    }

    /**
     * Check if Gregorian year is leap
     *
     * @param int $year
     * @return bool
     */
    private function isLeapGregorian(int $year): bool
    {
        return (($year % 4 == 0) && ($year % 100 != 0)) || ($year % 400 == 0);
    }

    /**
     * Get number of days in Gregorian month
     *
     * @param int $month
     * @param int $year
     * @return int
     */
    private function getGregorianMonthDays(int $month, int $year): int
    {
        $days = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        
        if ($month == 2 && $this->isLeapGregorian($year)) {
            return 29;
        }
        
        return $days[$month - 1];
    }
}