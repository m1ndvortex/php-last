<?php

namespace App\Http\Controllers;

use App\Services\LocalizationService;
use App\Services\CalendarService;
use App\Services\NumberService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LocalizationController extends Controller
{
    protected LocalizationService $localizationService;
    protected CalendarService $calendarService;
    protected NumberService $numberService;

    public function __construct(
        LocalizationService $localizationService,
        CalendarService $calendarService,
        NumberService $numberService
    ) {
        $this->localizationService = $localizationService;
        $this->calendarService = $calendarService;
        $this->numberService = $numberService;
    }

    /**
     * Switch application language
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function switchLanguage(Request $request): JsonResponse
    {
        $request->validate([
            'locale' => 'required|string|in:en,fa'
        ]);

        $locale = $request->input('locale');
        $success = $this->localizationService->switchLanguage($locale);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid locale provided'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Language switched successfully',
            'data' => [
                'locale' => $locale,
                'direction' => $this->localizationService->getDirection($locale),
                'name' => $this->localizationService->getLocaleName($locale)
            ]
        ]);
    }

    /**
     * Get current locale information
     *
     * @return JsonResponse
     */
    public function getCurrentLocale(): JsonResponse
    {
        $locale = $this->localizationService->getCurrentLocale();

        return response()->json([
            'success' => true,
            'data' => [
                'locale' => $locale,
                'direction' => $this->localizationService->getDirection($locale),
                'name' => $this->localizationService->getLocaleName($locale),
                'is_rtl' => $this->localizationService->isRTL()
            ]
        ]);
    }

    /**
     * Get translations for a specific locale
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTranslations(Request $request): JsonResponse
    {
        $locale = $request->query('locale', $this->localizationService->getCurrentLocale());

        if (!$this->localizationService->isValidLocale($locale)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid locale provided'
            ], 400);
        }

        $translations = $this->localizationService->getTranslations($locale);

        return response()->json([
            'success' => true,
            'data' => [
                'locale' => $locale,
                'translations' => $translations
            ]
        ]);
    }

    /**
     * Get supported locales
     *
     * @return JsonResponse
     */
    public function getSupportedLocales(): JsonResponse
    {
        $locales = $this->localizationService->getSupportedLocales();
        $localeData = [];

        foreach ($locales as $locale) {
            $localeData[] = [
                'code' => $locale,
                'name' => $this->localizationService->getLocaleName($locale),
                'direction' => $this->localizationService->getDirection($locale)
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $localeData
        ]);
    }

    /**
     * Get calendar information
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCalendarInfo(Request $request): JsonResponse
    {
        $type = $request->query('type', 'gregorian');
        $date = $request->query('date');

        if ($date) {
            try {
                if ($type === 'jalali') {
                    $calendarData = $this->calendarService->gregorianToJalali($date);
                } else {
                    $calendarData = $this->calendarService->getCurrentDate('gregorian');
                }
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid date provided'
                ], 400);
            }
        } else {
            $calendarData = $this->calendarService->getCurrentDate($type);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'type' => $type,
                'calendar' => $calendarData
            ]
        ]);
    }

    /**
     * Convert date between calendars
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function convertDate(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|string',
            'from' => 'required|string|in:gregorian,jalali',
            'to' => 'required|string|in:gregorian,jalali'
        ]);

        $date = $request->input('date');
        $from = $request->input('from');
        $to = $request->input('to');

        try {
            if ($from === 'gregorian' && $to === 'jalali') {
                $result = $this->calendarService->gregorianToJalali($date);
            } elseif ($from === 'jalali' && $to === 'gregorian') {
                // Parse Jalali date (assuming format: YYYY/MM/DD)
                $parts = explode('/', $date);
                if (count($parts) !== 3) {
                    throw new \InvalidArgumentException('Invalid Jalali date format');
                }
                
                $gregorianDate = $this->calendarService->jalaliToGregorian(
                    (int) $parts[0],
                    (int) $parts[1],
                    (int) $parts[2]
                );
                
                $result = [
                    'year' => $gregorianDate->year,
                    'month' => $gregorianDate->month,
                    'day' => $gregorianDate->day,
                    'formatted' => $gregorianDate->format('Y/m/d')
                ];
            } else {
                $result = ['formatted' => $date]; // Same calendar
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'original' => $date,
                    'converted' => $result,
                    'from' => $from,
                    'to' => $to
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Date conversion failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Format number according to locale
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function formatNumber(Request $request): JsonResponse
    {
        $request->validate([
            'number' => 'required|numeric',
            'locale' => 'string|in:en,fa',
            'type' => 'string|in:number,currency,percentage',
            'decimals' => 'integer|min:0|max:10',
            'currency' => 'string'
        ]);

        $number = $request->input('number');
        $locale = $request->input('locale', $this->localizationService->getCurrentLocale());
        $type = $request->input('type', 'number');
        $decimals = $request->input('decimals', 0);
        $currency = $request->input('currency', 'USD');

        switch ($type) {
            case 'currency':
                $formatted = $this->numberService->formatCurrency($number, $locale, $currency);
                break;
            case 'percentage':
                $formatted = $this->numberService->formatPercentage($number, $locale, $decimals);
                break;
            default:
                $formatted = $this->numberService->formatNumber($number, $locale, $decimals);
                break;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'original' => $number,
                'formatted' => $formatted,
                'locale' => $locale,
                'type' => $type
            ]
        ]);
    }

    /**
     * Convert number to words
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function numberToWords(Request $request): JsonResponse
    {
        $request->validate([
            'number' => 'required|integer',
            'locale' => 'string|in:en,fa'
        ]);

        $number = $request->input('number');
        $locale = $request->input('locale', $this->localizationService->getCurrentLocale());

        if ($locale === 'fa') {
            $words = $this->numberService->numberToPersianWords($number);
        } else {
            // For English, we'd need to implement or use a library
            // For now, just return the number as string
            $words = (string) $number;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'number' => $number,
                'words' => $words,
                'locale' => $locale
            ]
        ]);
    }
}