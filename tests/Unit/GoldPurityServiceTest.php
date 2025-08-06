<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\GoldPurityService;

class GoldPurityServiceTest extends TestCase
{
    protected GoldPurityService $goldPurityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->goldPurityService = app(GoldPurityService::class);
    }

    public function test_can_get_standard_purities()
    {
        $purities = $this->goldPurityService->getStandardPurities();
        
        $this->assertIsArray($purities);
        $this->assertNotEmpty($purities);
        
        // Check that each purity has required fields
        foreach ($purities as $purity) {
            $this->assertArrayHasKey('karat', $purity);
            $this->assertArrayHasKey('purity', $purity);
            $this->assertArrayHasKey('percentage', $purity);
            $this->assertArrayHasKey('display', $purity);
            $this->assertArrayHasKey('label', $purity);
        }
    }

    public function test_can_validate_purity()
    {
        // Valid purities
        $this->assertTrue($this->goldPurityService->validatePurity(18));
        $this->assertTrue($this->goldPurityService->validatePurity(24));
        $this->assertTrue($this->goldPurityService->validatePurity(1));
        
        // Invalid purities
        $this->assertFalse($this->goldPurityService->validatePurity(0));
        $this->assertFalse($this->goldPurityService->validatePurity(25));
        $this->assertFalse($this->goldPurityService->validatePurity(-1));
    }

    public function test_can_format_purity_display()
    {
        // English formatting
        $englishDisplay = $this->goldPurityService->formatPurityDisplay(18, 'en');
        $this->assertEquals('18.0K', $englishDisplay);
        
        // Persian formatting
        $persianDisplay = $this->goldPurityService->formatPurityDisplay(18, 'fa');
        $this->assertStringContainsString('عیار', $persianDisplay);
    }

    public function test_can_convert_numerals()
    {
        $persianNumber = $this->goldPurityService->convertToPersianNumerals('18.5');
        $this->assertEquals('۱۸٫۵', $persianNumber);
        
        $englishNumber = $this->goldPurityService->convertToEnglishNumerals('۱۸٫۵');
        $this->assertEquals('18.5', $englishNumber);
    }

    public function test_can_parse_purity_input()
    {
        // English input
        $this->assertEquals(18.5, $this->goldPurityService->parsePurityInput('18.5'));
        
        // Persian input
        $this->assertEquals(18.5, $this->goldPurityService->parsePurityInput('۱۸٫۵'));
        
        // Invalid input
        $this->assertNull($this->goldPurityService->parsePurityInput('invalid'));
        $this->assertNull($this->goldPurityService->parsePurityInput('25'));
    }

    public function test_can_get_purity_ranges()
    {
        $ranges = $this->goldPurityService->getPurityRanges();
        
        $this->assertIsArray($ranges);
        $this->assertNotEmpty($ranges);
        
        foreach ($ranges as $range) {
            $this->assertArrayHasKey('min', $range);
            $this->assertArrayHasKey('max', $range);
            $this->assertArrayHasKey('label', $range);
            $this->assertArrayHasKey('min_display', $range);
            $this->assertArrayHasKey('max_display', $range);
        }
    }

    public function test_can_convert_karat_to_percentage()
    {
        $this->assertEquals(75.0, $this->goldPurityService->convertKaratToPercentage(18));
        $this->assertEquals(100.0, $this->goldPurityService->convertKaratToPercentage(24));
    }

    public function test_can_find_closest_standard_purity()
    {
        $closest = $this->goldPurityService->findClosestStandardPurity(17.8);
        $this->assertEquals(18, $closest['purity']);
        
        $closest = $this->goldPurityService->findClosestStandardPurity(23.5);
        $this->assertEquals(24, $closest['purity']);
    }
}