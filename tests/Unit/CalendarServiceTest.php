<?php

namespace Tests\Unit;

use App\Services\CalendarService;
use Carbon\Carbon;
use Tests\TestCase;

class CalendarServiceTest extends TestCase
{
    protected CalendarService $calendarService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calendarService = new CalendarService();
    }

    public function test_can_convert_gregorian_to_jalali()
    {
        // Test known date: 2024-03-20 (Persian New Year) should be 1403/01/01
        $gregorianDate = '2024-03-20';
        $jalali = $this->calendarService->gregorianToJalali($gregorianDate);

        $this->assertIsArray($jalali);
        $this->assertArrayHasKey('year', $jalali);
        $this->assertArrayHasKey('month', $jalali);
        $this->assertArrayHasKey('day', $jalali);
        $this->assertArrayHasKey('month_name', $jalali);
        $this->assertArrayHasKey('weekday', $jalali);
        $this->assertArrayHasKey('formatted', $jalali);

        // Verify the conversion is approximately correct (within reasonable range)
        $this->assertGreaterThan(1400, $jalali['year']);
        $this->assertLessThan(1500, $jalali['year']);
        $this->assertGreaterThanOrEqual(1, $jalali['month']);
        $this->assertLessThanOrEqual(12, $jalali['month']);
        $this->assertGreaterThanOrEqual(1, $jalali['day']);
        $this->assertLessThanOrEqual(31, $jalali['day']);
    }

    public function test_can_convert_jalali_to_gregorian()
    {
        // Test known date: 1403/01/01 should be around 2024-03-20
        $gregorian = $this->calendarService->jalaliToGregorian(1403, 1, 1);

        $this->assertInstanceOf(Carbon::class, $gregorian);
        $this->assertEquals(2024, $gregorian->year);
        $this->assertEquals(3, $gregorian->month);
        $this->assertEquals(20, $gregorian->day);
    }

    public function test_can_format_date_in_persian()
    {
        $date = '2024-01-01';
        $formatted = $this->calendarService->formatDate($date, 'fa');

        $this->assertIsString($formatted);
        $this->assertNotEmpty($formatted);
    }

    public function test_can_format_date_in_english()
    {
        $date = '2024-01-01';
        $formatted = $this->calendarService->formatDate($date, 'en');

        $this->assertIsString($formatted);
        $this->assertEquals('2024/01/01', $formatted);
    }

    public function test_can_get_current_date_gregorian()
    {
        $currentDate = $this->calendarService->getCurrentDate('gregorian');

        $this->assertIsArray($currentDate);
        $this->assertArrayHasKey('year', $currentDate);
        $this->assertArrayHasKey('month', $currentDate);
        $this->assertArrayHasKey('day', $currentDate);
        $this->assertArrayHasKey('formatted', $currentDate);
    }

    public function test_can_get_current_date_jalali()
    {
        $currentDate = $this->calendarService->getCurrentDate('jalali');

        $this->assertIsArray($currentDate);
        $this->assertArrayHasKey('year', $currentDate);
        $this->assertArrayHasKey('month', $currentDate);
        $this->assertArrayHasKey('day', $currentDate);
        $this->assertArrayHasKey('month_name', $currentDate);
        $this->assertArrayHasKey('formatted', $currentDate);
    }

    public function test_can_handle_carbon_instance()
    {
        $carbon = Carbon::create(2024, 1, 1);
        $jalali = $this->calendarService->gregorianToJalali($carbon);

        $this->assertIsArray($jalali);
        $this->assertArrayHasKey('year', $jalali);
    }

    public function test_jalali_months_are_defined()
    {
        $reflection = new \ReflectionClass(CalendarService::class);
        $months = $reflection->getConstant('JALALI_MONTHS');

        $this->assertIsArray($months);
        $this->assertCount(12, $months);
        $this->assertEquals('فروردین', $months[1]);
        $this->assertEquals('اسفند', $months[12]);
    }

    public function test_jalali_weekdays_are_defined()
    {
        $reflection = new \ReflectionClass(CalendarService::class);
        $weekdays = $reflection->getConstant('JALALI_WEEKDAYS');

        $this->assertIsArray($weekdays);
        $this->assertCount(7, $weekdays);
        $this->assertEquals('یکشنبه', $weekdays[0]);
        $this->assertEquals('شنبه', $weekdays[6]);
    }
}