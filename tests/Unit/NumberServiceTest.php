<?php

namespace Tests\Unit;

use App\Services\NumberService;
use Tests\TestCase;

class NumberServiceTest extends TestCase
{
    protected NumberService $numberService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->numberService = new NumberService();
    }

    public function test_can_convert_to_persian_numerals()
    {
        $result = $this->numberService->toPersianNumerals('123456789');
        
        $this->assertEquals('۱۲۳۴۵۶۷۸۹', $result);
    }

    public function test_can_convert_to_english_numerals()
    {
        $result = $this->numberService->toEnglishNumerals('۱۲۳۴۵۶۷۸۹');
        
        $this->assertEquals('123456789', $result);
    }

    public function test_can_format_number_in_english()
    {
        $result = $this->numberService->formatNumber(1234.56, 'en', 2);
        
        $this->assertEquals('1,234.56', $result);
    }

    public function test_can_format_number_in_persian()
    {
        $result = $this->numberService->formatNumber(1234.56, 'fa', 2);
        
        $this->assertEquals('۱،۲۳۴/۵۶', $result);
    }

    public function test_can_format_currency_in_english()
    {
        $result = $this->numberService->formatCurrency(1234.56, 'en', 'USD');
        
        $this->assertEquals('$1,234.56', $result);
    }

    public function test_can_format_currency_in_persian()
    {
        $result = $this->numberService->formatCurrency(1234.56, 'fa', 'IRR');
        
        $this->assertEquals('۱،۲۳۴/۵۶ ریال', $result);
    }

    public function test_can_convert_number_to_persian_words()
    {
        $result = $this->numberService->numberToPersianWords(123);
        
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('یکصد', $result);
    }

    public function test_can_handle_zero_in_persian_words()
    {
        $result = $this->numberService->numberToPersianWords(0);
        
        $this->assertEquals('صفر', $result);
    }

    public function test_can_handle_negative_numbers_in_persian_words()
    {
        $result = $this->numberService->numberToPersianWords(-123);
        
        $this->assertStringStartsWith('منفی', $result);
    }

    public function test_can_parse_persian_number()
    {
        $result = $this->numberService->parsePersianNumber('۱۲۳۴/۵۶');
        
        $this->assertEquals(1234.56, $result);
    }

    public function test_can_parse_persian_integer()
    {
        $result = $this->numberService->parsePersianNumber('۱۲۳۴');
        
        $this->assertEquals(1234, $result);
    }

    public function test_returns_null_for_invalid_persian_number()
    {
        $result = $this->numberService->parsePersianNumber('invalid');
        
        $this->assertNull($result);
    }

    public function test_can_get_persian_ordinal()
    {
        $result = $this->numberService->getPersianOrdinal(1);
        
        $this->assertStringEndsWith('م', $result);
    }

    public function test_can_format_percentage_in_english()
    {
        $result = $this->numberService->formatPercentage(25.5, 'en', 1);
        
        $this->assertEquals('25.5%', $result);
    }

    public function test_can_format_percentage_in_persian()
    {
        $result = $this->numberService->formatPercentage(25.5, 'fa', 1);
        
        $this->assertEquals('۲۵/۵٪', $result);
    }

    public function test_persian_numerals_constant_is_defined()
    {
        $reflection = new \ReflectionClass(NumberService::class);
        $numerals = $reflection->getConstant('PERSIAN_NUMERALS');

        $this->assertIsArray($numerals);
        $this->assertCount(10, $numerals);
        $this->assertEquals('۰', $numerals['0']);
        $this->assertEquals('۹', $numerals['9']);
    }

    public function test_english_numerals_constant_is_defined()
    {
        $reflection = new \ReflectionClass(NumberService::class);
        $numerals = $reflection->getConstant('ENGLISH_NUMERALS');

        $this->assertIsArray($numerals);
        $this->assertCount(10, $numerals);
        $this->assertEquals('0', $numerals['۰']);
        $this->assertEquals('9', $numerals['۹']);
    }

    public function test_can_handle_float_input_for_persian_numerals()
    {
        $result = $this->numberService->toPersianNumerals(123.45);
        
        $this->assertEquals('۱۲۳.۴۵', $result);
    }

    public function test_can_handle_integer_input_for_persian_numerals()
    {
        $result = $this->numberService->toPersianNumerals(123);
        
        $this->assertEquals('۱۲۳', $result);
    }
}