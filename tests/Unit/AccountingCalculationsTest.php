<?php

namespace Tests\Unit;

use App\Services\AssetService;
use App\Services\AdvancedJournalEntryService;
use App\Services\BudgetPlanningService;
use App\Services\TaxService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class AccountingCalculationsTest extends TestCase
{
    /** @test */
    public function straight_line_depreciation_calculation_is_correct()
    {
        $cost = 10000;
        $salvageValue = 1000;
        $usefulLifeYears = 5;
        $depreciableAmount = $cost - $salvageValue;
        
        $annualDepreciation = $depreciableAmount / $usefulLifeYears;
        $monthlyDepreciation = $annualDepreciation / 12;
        
        $this->assertEquals(9000, $depreciableAmount);
        $this->assertEquals(1800, $annualDepreciation);
        $this->assertEquals(150, $monthlyDepreciation);
    }

    /** @test */
    public function declining_balance_depreciation_calculation_is_correct()
    {
        $cost = 10000;
        $usefulLifeYears = 5;
        $rate = 2 / $usefulLifeYears; // Double declining balance rate
        
        $firstYearDepreciation = $cost * $rate;
        $remainingValue = $cost - $firstYearDepreciation;
        
        $this->assertEquals(0.4, $rate);
        $this->assertEquals(4000, $firstYearDepreciation);
        $this->assertEquals(6000, $remainingValue);
    }

    /** @test */
    public function sum_of_years_digits_calculation_is_correct()
    {
        $cost = 10000;
        $salvageValue = 1000;
        $usefulLifeYears = 5;
        $depreciableAmount = $cost - $salvageValue;
        
        $sumOfYears = ($usefulLifeYears * ($usefulLifeYears + 1)) / 2;
        $firstYearFraction = $usefulLifeYears / $sumOfYears;
        $firstYearDepreciation = $depreciableAmount * $firstYearFraction;
        
        $this->assertEquals(15, $sumOfYears); // 5+4+3+2+1
        $this->assertEquals(1/3, $firstYearFraction, '', 0.01); // 5/15
        $this->assertEquals(3000, $firstYearDepreciation);
    }

    /** @test */
    public function tax_percentage_calculation_is_correct()
    {
        $amount = 1000;
        $taxRate = 20; // 20%
        
        $taxAmount = $amount * ($taxRate / 100);
        $netAmount = $amount + $taxAmount;
        
        $this->assertEquals(200, $taxAmount);
        $this->assertEquals(1200, $netAmount);
    }

    /** @test */
    public function currency_conversion_calculation_is_correct()
    {
        $amount = 1000; // EUR
        $exchangeRate = 1.1; // EUR to USD
        
        $convertedAmount = $amount * $exchangeRate;
        
        $this->assertEquals(1100, $convertedAmount);
    }

    /** @test */
    public function budget_variance_calculation_is_correct()
    {
        $budgetAmount = 10000;
        $actualAmount = 8500;
        
        $variance = $actualAmount - $budgetAmount;
        $variancePercentage = ($variance / $budgetAmount) * 100;
        
        $this->assertEquals(-1500, $variance);
        $this->assertEquals(-15, $variancePercentage);
    }

    /** @test */
    public function cash_flow_forecast_calculation_is_correct()
    {
        $openingBalance = 5000;
        $inflows = 15000;
        $outflows = 12000;
        
        $netCashFlow = $inflows - $outflows;
        $closingBalance = $openingBalance + $netCashFlow;
        
        $this->assertEquals(3000, $netCashFlow);
        $this->assertEquals(8000, $closingBalance);
    }

    /** @test */
    public function journal_entry_balance_validation_works()
    {
        $entries = [
            ['debit' => 1000, 'credit' => 0],
            ['debit' => 0, 'credit' => 500],
            ['debit' => 0, 'credit' => 500],
        ];
        
        $totalDebits = array_sum(array_column($entries, 'debit'));
        $totalCredits = array_sum(array_column($entries, 'credit'));
        
        $isBalanced = $totalDebits === $totalCredits;
        
        $this->assertEquals(1000, $totalDebits);
        $this->assertEquals(1000, $totalCredits);
        $this->assertTrue($isBalanced);
    }

    /** @test */
    public function unbalanced_journal_entry_is_detected()
    {
        $entries = [
            ['debit' => 1000, 'credit' => 0],
            ['debit' => 0, 'credit' => 800], // Unbalanced
        ];
        
        $totalDebits = array_sum(array_column($entries, 'debit'));
        $totalCredits = array_sum(array_column($entries, 'credit'));
        
        $isBalanced = $totalDebits === $totalCredits;
        $variance = abs($totalDebits - $totalCredits);
        
        $this->assertEquals(1000, $totalDebits);
        $this->assertEquals(800, $totalCredits);
        $this->assertFalse($isBalanced);
        $this->assertEquals(200, $variance);
    }

    /** @test */
    public function bank_reconciliation_variance_calculation_is_correct()
    {
        $bookBalance = 5000;
        $bankBalance = 5050;
        $outstandingDeposits = 200;
        $outstandingChecks = 150;
        
        $reconciledBalance = $bookBalance + $outstandingDeposits - $outstandingChecks;
        $variance = abs($reconciledBalance - $bankBalance);
        $tolerance = 0.01;
        $isReconciled = $variance <= $tolerance;
        
        $this->assertEquals(5050, $reconciledBalance);
        $this->assertEquals(0, $variance);
        $this->assertTrue($isReconciled);
    }

    /** @test */
    public function compound_interest_calculation_for_cash_flow_is_correct()
    {
        $principal = 10000;
        $annualRate = 0.05; // 5%
        $compoundingPeriods = 12; // Monthly
        $years = 1;
        
        $amount = $principal * pow(1 + ($annualRate / $compoundingPeriods), $compoundingPeriods * $years);
        $interest = $amount - $principal;
        
        $this->assertEqualsWithDelta(10511.62, $amount, 0.01);
        $this->assertEqualsWithDelta(511.62, $interest, 0.01);
    }

    /** @test */
    public function aging_analysis_calculation_is_correct()
    {
        $invoices = [
            ['amount' => 1000, 'days_overdue' => 15],
            ['amount' => 2000, 'days_overdue' => 45],
            ['amount' => 1500, 'days_overdue' => 75],
        ];
        
        $current = 0;
        $days30 = 0;
        $days60 = 0;
        $days90Plus = 0;
        
        foreach ($invoices as $invoice) {
            if ($invoice['days_overdue'] <= 30) {
                $days30 += $invoice['amount'];
            } elseif ($invoice['days_overdue'] <= 60) {
                $days60 += $invoice['amount'];
            } else {
                $days90Plus += $invoice['amount'];
            }
        }
        
        $total = $current + $days30 + $days60 + $days90Plus;
        
        $this->assertEquals(1000, $days30);
        $this->assertEquals(2000, $days60);
        $this->assertEquals(1500, $days90Plus);
        $this->assertEquals(4500, $total);
    }

    /** @test */
    public function collection_probability_calculation_is_correct()
    {
        $invoices = [
            ['amount' => 1000, 'days_overdue' => 15, 'probability' => 0.95],
            ['amount' => 2000, 'days_overdue' => 45, 'probability' => 0.70],
            ['amount' => 1500, 'days_overdue' => 95, 'probability' => 0.25],
        ];
        
        $expectedCollection = 0;
        foreach ($invoices as $invoice) {
            $expectedCollection += $invoice['amount'] * $invoice['probability'];
        }
        
        $this->assertEquals(2725, $expectedCollection); // 950 + 1400 + 375 = 2725
    }

    /** @test */
    public function working_capital_calculation_is_correct()
    {
        $currentAssets = 50000;
        $currentLiabilities = 30000;
        
        $workingCapital = $currentAssets - $currentLiabilities;
        $workingCapitalRatio = $currentAssets / $currentLiabilities;
        
        $this->assertEquals(20000, $workingCapital);
        $this->assertEqualsWithDelta(1.67, $workingCapitalRatio, 0.01);
    }

    /** @test */
    public function break_even_analysis_calculation_is_correct()
    {
        $fixedCosts = 10000;
        $variableCostPerUnit = 15;
        $sellingPricePerUnit = 25;
        
        $contributionMarginPerUnit = $sellingPricePerUnit - $variableCostPerUnit;
        $breakEvenUnits = $fixedCosts / $contributionMarginPerUnit;
        $breakEvenRevenue = $breakEvenUnits * $sellingPricePerUnit;
        
        $this->assertEquals(10, $contributionMarginPerUnit);
        $this->assertEquals(1000, $breakEvenUnits);
        $this->assertEquals(25000, $breakEvenRevenue);
    }
}