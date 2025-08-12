<?php

namespace App\Services;

use App\Models\TaxRate;
use App\Models\Transaction;
use App\Models\Account;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class TaxService
{
    public function calculateTax(float $amount, array $taxRateIds, ?Carbon $date = null): array
    {
        return TaxRate::calculateTotalTax($amount, $taxRateIds, $date);
    }

    public function getActiveTaxRates(?Carbon $date = null, ?string $type = null): Collection
    {
        $query = TaxRate::active()->effectiveOn($date);
        
        if ($type) {
            $query->byType($type);
        }
        
        return $query->get();
    }

    public function generateTaxReport(Carbon $startDate, Carbon $endDate, ?string $taxType = null): array
    {
        $taxRates = $this->getActiveTaxRates(null, $taxType);
        
        $report = [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'tax_summary' => [],
            'total_tax_collected' => 0,
            'total_tax_paid' => 0,
            'net_tax_liability' => 0,
        ];

        foreach ($taxRates as $taxRate) {
            $taxData = $this->calculateTaxForPeriod($taxRate, $startDate, $endDate);
            
            $report['tax_summary'][] = [
                'tax_name' => $taxRate->localized_name,
                'tax_rate' => $taxRate->rate,
                'tax_type' => $taxRate->type,
                'taxable_amount' => $taxData['taxable_amount'],
                'tax_amount' => $taxData['tax_amount'],
                'transactions_count' => $taxData['transactions_count'],
            ];
            
            if (in_array($taxRate->type, ['sales', 'vat'])) {
                $report['total_tax_collected'] += $taxData['tax_amount'];
            } else {
                $report['total_tax_paid'] += $taxData['tax_amount'];
            }
        }
        
        $report['net_tax_liability'] = $report['total_tax_collected'] - $report['total_tax_paid'];
        
        return $report;
    }

    private function calculateTaxForPeriod(TaxRate $taxRate, Carbon $startDate, Carbon $endDate): array
    {
        // This is a simplified calculation
        // In a real implementation, you would need to track tax amounts in transactions
        
        $transactions = Transaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->where('type', 'invoice') // Assuming invoices have tax
            ->get();
        
        $taxableAmount = 0;
        $taxAmount = 0;
        $transactionsCount = 0;
        
        foreach ($transactions as $transaction) {
            // This would need to be enhanced to actually calculate tax from transaction data
            $transactionTaxableAmount = $transaction->total_amount;
            $transactionTaxAmount = $transactionTaxableAmount * $taxRate->rate;
            
            $taxableAmount += $transactionTaxableAmount;
            $taxAmount += $transactionTaxAmount;
            $transactionsCount++;
        }
        
        return [
            'taxable_amount' => $taxableAmount,
            'tax_amount' => $taxAmount,
            'transactions_count' => $transactionsCount,
        ];
    }

    public function generateVATReport(Carbon $startDate, Carbon $endDate): array
    {
        $vatRates = TaxRate::where('type', 'vat')->active()->get();
        
        $report = [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'sales_vat' => [],
            'purchase_vat' => [],
            'total_sales_vat' => 0,
            'total_purchase_vat' => 0,
            'net_vat_payable' => 0,
        ];

        // Calculate sales VAT (output VAT)
        foreach ($vatRates as $vatRate) {
            $salesVat = $this->calculateVATForTransactionType($vatRate, $startDate, $endDate, 'sales');
            
            if ($salesVat['tax_amount'] > 0) {
                $report['sales_vat'][] = [
                    'vat_rate' => $vatRate->rate,
                    'taxable_sales' => $salesVat['taxable_amount'],
                    'vat_amount' => $salesVat['tax_amount'],
                ];
                
                $report['total_sales_vat'] += $salesVat['tax_amount'];
            }
        }

        // Calculate purchase VAT (input VAT)
        foreach ($vatRates as $vatRate) {
            $purchaseVat = $this->calculateVATForTransactionType($vatRate, $startDate, $endDate, 'purchase');
            
            if ($purchaseVat['tax_amount'] > 0) {
                $report['purchase_vat'][] = [
                    'vat_rate' => $vatRate->rate,
                    'taxable_purchases' => $purchaseVat['taxable_amount'],
                    'vat_amount' => $purchaseVat['tax_amount'],
                ];
                
                $report['total_purchase_vat'] += $purchaseVat['tax_amount'];
            }
        }
        
        $report['net_vat_payable'] = $report['total_sales_vat'] - $report['total_purchase_vat'];
        
        return $report;
    }

    private function calculateVATForTransactionType(TaxRate $vatRate, Carbon $startDate, Carbon $endDate, string $type): array
    {
        // This would need to be enhanced based on how VAT is tracked in your system
        // For now, return placeholder data
        
        return [
            'taxable_amount' => 0,
            'tax_amount' => 0,
        ];
    }

    public function createTaxTransaction(array $taxData, Carbon $transactionDate): Transaction
    {
        // Create a transaction for tax payment/collection
        
        $entries = [];
        
        // Tax liability account (credit)
        $entries[] = [
            'account_id' => $this->getTaxLiabilityAccount($taxData['tax_type'])->id,
            'debit_amount' => 0,
            'credit_amount' => $taxData['tax_amount'],
            'description' => 'Tax liability for ' . $taxData['description'],
        ];
        
        // Cash/Bank account (debit for payment, credit for collection)
        $cashAccount = $this->getCashAccount();
        $entries[] = [
            'account_id' => $cashAccount->id,
            'debit_amount' => $taxData['is_payment'] ? $taxData['tax_amount'] : 0,
            'credit_amount' => $taxData['is_payment'] ? 0 : $taxData['tax_amount'],
            'description' => ($taxData['is_payment'] ? 'Tax payment' : 'Tax collection') . ' for ' . $taxData['description'],
        ];
        
        return app(AccountingService::class)->createTransaction([
            'description' => 'Tax ' . ($taxData['is_payment'] ? 'payment' : 'collection') . ' - ' . $taxData['description'],
            'transaction_date' => $transactionDate,
            'type' => 'journal',
            'total_amount' => $taxData['tax_amount'],
            'entries' => $entries,
        ]);
    }

    private function getTaxLiabilityAccount(string $taxType): Account
    {
        // Get or create tax liability account based on tax type
        return Account::firstOrCreate([
            'code' => '2100-' . strtoupper($taxType),
            'name' => ucfirst($taxType) . ' Tax Payable',
            'type' => 'liability',
            'subtype' => 'current_liability',
        ]);
    }

    private function getCashAccount(): Account
    {
        // Get the primary cash account
        return Account::where('type', 'asset')
            ->where('subtype', 'current_asset')
            ->where('code', 'like', '1010%')
            ->first() ?? Account::where('name', 'like', '%cash%')->first();
    }

    public function scheduleRecurringTaxPayment(array $taxData): void
    {
        // This would create a recurring transaction for regular tax payments
        // Implementation would depend on your recurring transaction system
    }

    /**
     * Enhanced tax calculation with multiple tax types and compliance
     */
    public function calculateEnhancedTax(float $amount, array $taxCodes, ?Carbon $effectiveDate = null): array
    {
        $effectiveDate = $effectiveDate ?? now();
        $result = [
            'base_amount' => $amount,
            'effective_date' => $effectiveDate->toDateString(),
            'tax_calculations' => [],
            'total_tax' => 0,
            'net_amount' => $amount,
            'compliance_notes' => [],
        ];

        foreach ($taxCodes as $taxCode) {
            $taxRate = TaxRate::where('code', $taxCode)
                ->where('is_active', true)
                ->where('effective_from', '<=', $effectiveDate)
                ->where(function ($query) use ($effectiveDate) {
                    $query->whereNull('effective_to')
                        ->orWhere('effective_to', '>=', $effectiveDate);
                })
                ->first();

            if (!$taxRate) {
                $result['compliance_notes'][] = "Tax code {$taxCode} not found or not effective on {$effectiveDate->toDateString()}";
                continue;
            }

            $taxAmount = $this->calculateTaxByType($amount, $taxRate);
            
            $result['tax_calculations'][] = [
                'tax_code' => $taxCode,
                'tax_name' => $taxRate->name,
                'tax_type' => $taxRate->type,
                'rate' => $taxRate->rate,
                'calculation_method' => $taxRate->calculation_method ?? 'percentage',
                'taxable_amount' => $amount,
                'tax_amount' => $taxAmount,
                'account_id' => $taxRate->tax_account_id ?? null,
            ];

            $result['total_tax'] += $taxAmount;
        }

        $result['net_amount'] = $amount + $result['total_tax'];

        return $result;
    }

    /**
     * Generate comprehensive tax compliance report
     */
    public function generateTaxComplianceReport(Carbon $startDate, Carbon $endDate, string $taxType = null): array
    {
        $report = [
            'report_type' => 'tax_compliance',
            'period_start' => $startDate->toDateString(),
            'period_end' => $endDate->toDateString(),
            'generated_at' => now()->toISOString(),
            'tax_type' => $taxType,
            'summary' => [
                'total_taxable_sales' => 0,
                'total_tax_collected' => 0,
                'total_taxable_purchases' => 0,
                'total_tax_paid' => 0,
                'net_tax_liability' => 0,
            ],
            'tax_details' => [],
            'transactions' => [],
            'compliance_issues' => [],
        ];

        // Get all tax-related transactions
        $transactions = Transaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->whereHas('entries', function ($query) {
                $query->whereHas('account', function ($q) {
                    $q->where('type', 'liability')
                      ->where(function ($subQ) {
                          $subQ->where('name', 'like', '%tax%')
                               ->orWhere('name', 'like', '%vat%')
                               ->orWhere('code', 'like', '23%'); // Tax liability accounts
                      });
                });
            })
            ->with(['entries.account'])
            ->get();

        $taxDetails = [];

        foreach ($transactions as $transaction) {
            foreach ($transaction->entries as $entry) {
                if ($entry->account->type === 'liability' && 
                    (stripos($entry->account->name, 'tax') !== false || 
                     stripos($entry->account->name, 'vat') !== false ||
                     strpos($entry->account->code, '23') === 0)) {
                    
                    $taxCode = $this->extractTaxCodeFromAccount($entry->account);
                    
                    if (!isset($taxDetails[$taxCode])) {
                        $taxDetails[$taxCode] = [
                            'tax_code' => $taxCode,
                            'tax_name' => $entry->account->name,
                            'account_id' => $entry->account_id,
                            'collected' => 0,
                            'paid' => 0,
                            'balance' => 0,
                            'transactions_count' => 0,
                        ];
                    }

                    $taxDetails[$taxCode]['collected'] += $entry->credit_amount;
                    $taxDetails[$taxCode]['paid'] += $entry->debit_amount;
                    $taxDetails[$taxCode]['balance'] += $entry->credit_amount - $entry->debit_amount;
                    $taxDetails[$taxCode]['transactions_count']++;

                    $report['transactions'][] = [
                        'transaction_id' => $transaction->id,
                        'reference' => $transaction->reference_number,
                        'date' => $transaction->transaction_date->toDateString(),
                        'description' => $transaction->description,
                        'tax_code' => $taxCode,
                        'tax_amount' => $entry->credit_amount - $entry->debit_amount,
                        'type' => $entry->credit_amount > 0 ? 'collected' : 'paid',
                    ];
                }
            }
        }

        $report['tax_details'] = array_values($taxDetails);

        // Calculate summary
        foreach ($taxDetails as $detail) {
            $report['summary']['total_tax_collected'] += $detail['collected'];
            $report['summary']['total_tax_paid'] += $detail['paid'];
        }

        $report['summary']['net_tax_liability'] = 
            $report['summary']['total_tax_collected'] - $report['summary']['total_tax_paid'];

        // Check for compliance issues
        $report['compliance_issues'] = $this->checkComplianceIssues($report, $startDate, $endDate);

        return $report;
    }

    /**
     * Generate enhanced VAT return report
     */
    public function generateEnhancedVATReturn(Carbon $startDate, Carbon $endDate): array
    {
        $vatReturn = [
            'return_type' => 'vat_return',
            'period_start' => $startDate->toDateString(),
            'period_end' => $endDate->toDateString(),
            'generated_at' => now()->toISOString(),
            'boxes' => [],
            'summary' => [
                'vat_due_on_sales' => 0,
                'vat_due_on_acquisitions' => 0,
                'total_vat_due' => 0,
                'vat_reclaimed' => 0,
                'net_vat_due' => 0,
                'total_value_sales' => 0,
                'total_value_purchases' => 0,
            ],
        ];

        // Box 1: VAT due on sales and other outputs
        $salesVAT = $this->calculateVATOnSales($startDate, $endDate);
        $vatReturn['boxes']['box_1'] = [
            'description' => 'VAT due on sales and other outputs',
            'amount' => $salesVAT['vat_amount'],
            'net_amount' => $salesVAT['net_amount'],
        ];

        // Box 2: VAT due on acquisitions
        $acquisitionsVAT = $this->calculateVATOnAcquisitions($startDate, $endDate);
        $vatReturn['boxes']['box_2'] = [
            'description' => 'VAT due on acquisitions from other EU Member States',
            'amount' => $acquisitionsVAT['vat_amount'],
            'net_amount' => $acquisitionsVAT['net_amount'],
        ];

        // Box 3: Total VAT due (Box 1 + Box 2)
        $totalVATDue = $salesVAT['vat_amount'] + $acquisitionsVAT['vat_amount'];
        $vatReturn['boxes']['box_3'] = [
            'description' => 'Total VAT due (Box 1 + Box 2)',
            'amount' => $totalVATDue,
        ];

        // Box 4: VAT reclaimed on purchases and other inputs
        $purchasesVAT = $this->calculateVATOnPurchases($startDate, $endDate);
        $vatReturn['boxes']['box_4'] = [
            'description' => 'VAT reclaimed on purchases and other inputs',
            'amount' => $purchasesVAT['vat_amount'],
            'net_amount' => $purchasesVAT['net_amount'],
        ];

        // Box 5: Net VAT to be paid or reclaimed (Box 3 - Box 4)
        $netVAT = $totalVATDue - $purchasesVAT['vat_amount'];
        $vatReturn['boxes']['box_5'] = [
            'description' => 'Net VAT to be paid or reclaimed',
            'amount' => $netVAT,
        ];

        // Box 6: Total value of sales (excluding VAT)
        $vatReturn['boxes']['box_6'] = [
            'description' => 'Total value of sales and all other outputs excluding any VAT',
            'amount' => $salesVAT['net_amount'],
        ];

        // Box 7: Total value of purchases (excluding VAT)
        $vatReturn['boxes']['box_7'] = [
            'description' => 'Total value of purchases and all other inputs excluding any VAT',
            'amount' => $purchasesVAT['net_amount'],
        ];

        // Box 8: Total value of supplies of goods to other EU Member States
        $euSales = $this->calculateEUSales($startDate, $endDate);
        $vatReturn['boxes']['box_8'] = [
            'description' => 'Total value of supplies of goods to other EU Member States',
            'amount' => $euSales,
        ];

        // Box 9: Total value of acquisitions of goods from other EU Member States
        $vatReturn['boxes']['box_9'] = [
            'description' => 'Total value of acquisitions of goods from other EU Member States',
            'amount' => $acquisitionsVAT['net_amount'],
        ];

        // Update summary
        $vatReturn['summary'] = [
            'vat_due_on_sales' => $salesVAT['vat_amount'],
            'vat_due_on_acquisitions' => $acquisitionsVAT['vat_amount'],
            'total_vat_due' => $totalVATDue,
            'vat_reclaimed' => $purchasesVAT['vat_amount'],
            'net_vat_due' => $netVAT,
            'total_value_sales' => $salesVAT['net_amount'],
            'total_value_purchases' => $purchasesVAT['net_amount'],
        ];

        return $vatReturn;
    }

    /**
     * Create tax payment transaction
     */
    public function createTaxPaymentTransaction(string $taxCode, float $amount, Carbon $paymentDate, string $paymentMethod = 'bank_transfer'): Transaction
    {
        $taxRate = TaxRate::where('code', $taxCode)->where('is_active', true)->first();
        if (!$taxRate) {
            throw new \Exception("Tax code {$taxCode} not found");
        }

        $taxAccount = $taxRate->tax_account_id ? Account::find($taxRate->tax_account_id) : null;
        if (!$taxAccount) {
            // Create or find tax liability account
            $taxAccount = Account::firstOrCreate(
                ['code' => '2310'],
                [
                    'name' => 'Sales Tax Payable',
                    'name_persian' => 'مالیات فروش پرداختنی',
                    'type' => 'liability',
                    'is_active' => true,
                    'currency' => 'USD',
                    'opening_balance' => 0
                ]
            );
        }

        $cashAccount = Account::where('code', '1120')->first(); // Bank Account - Main
        if (!$cashAccount) {
            throw new \Exception('Bank account not found for tax payment');
        }

        $transactionData = [
            'reference_number' => 'TAX-PAY-' . $taxCode . '-' . $paymentDate->format('Y-m-d'),
            'description' => "Tax payment for {$taxCode}",
            'description_persian' => "پرداخت مالیات برای {$taxCode}",
            'transaction_date' => $paymentDate,
            'type' => 'tax_payment',
            'source_type' => 'tax_payment',
            'total_amount' => $amount,
            'entries' => [
                [
                    'account_id' => $taxAccount->id,
                    'debit_amount' => $amount,
                    'credit_amount' => 0,
                    'description' => "Tax payment - {$taxCode}",
                    'description_persian' => "پرداخت مالیات - {$taxCode}",
                ],
                [
                    'account_id' => $cashAccount->id,
                    'debit_amount' => 0,
                    'credit_amount' => $amount,
                    'description' => "Cash payment for tax - {$taxCode}",
                    'description_persian' => "پرداخت نقدی مالیات - {$taxCode}",
                ]
            ]
        ];

        $accountingService = app(AccountingService::class);
        return $accountingService->createTransaction($transactionData);
    }

    protected function calculateTaxByType(float $amount, TaxRate $taxRate): float
    {
        $calculationMethod = $taxRate->calculation_method ?? 'percentage';
        
        switch ($calculationMethod) {
            case 'percentage':
                return $amount * ($taxRate->rate / 100);
            case 'fixed':
                return $taxRate->rate;
            case 'tiered':
                return $this->calculateTieredTax($amount, $taxRate);
            default:
                return $amount * ($taxRate->rate / 100);
        }
    }

    protected function calculateTieredTax(float $amount, TaxRate $taxRate): float
    {
        // This would implement tiered tax calculation based on tax brackets
        // For now, return simple percentage calculation
        return $amount * ($taxRate->rate / 100);
    }

    protected function extractTaxCodeFromAccount(Account $account): string
    {
        // Extract tax code from account name or code
        if (stripos($account->name, 'VAT') !== false || stripos($account->code, 'VAT') !== false) {
            return 'VAT';
        } elseif (stripos($account->name, 'Sales Tax') !== false) {
            return 'SALES_TAX';
        } elseif (stripos($account->name, 'Income Tax') !== false) {
            return 'INCOME_TAX';
        } elseif (strpos($account->code, '2310') === 0) {
            return 'SALES_TAX';
        } elseif (strpos($account->code, '2320') === 0) {
            return 'INCOME_TAX';
        } elseif (strpos($account->code, '2330') === 0) {
            return 'VAT';
        }
        
        return 'UNKNOWN';
    }

    protected function calculateVATOnSales(Carbon $startDate, Carbon $endDate): array
    {
        // Calculate VAT on sales from invoice data
        $salesTransactions = Transaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->where('type', 'invoice')
            ->with('entries.account')
            ->get();

        $vatAmount = 0;
        $netAmount = 0;

        foreach ($salesTransactions as $transaction) {
            foreach ($transaction->entries as $entry) {
                if ($entry->account->type === 'revenue') {
                    $netAmount += $entry->credit_amount;
                } elseif (stripos($entry->account->name, 'vat') !== false && $entry->account->type === 'liability') {
                    $vatAmount += $entry->credit_amount;
                }
            }
        }

        return ['vat_amount' => $vatAmount, 'net_amount' => $netAmount];
    }

    protected function calculateVATOnAcquisitions(Carbon $startDate, Carbon $endDate): array
    {
        // This would calculate VAT on EU acquisitions
        // For now, return zero as this is specific to EU businesses
        return ['vat_amount' => 0, 'net_amount' => 0];
    }

    protected function calculateVATOnPurchases(Carbon $startDate, Carbon $endDate): array
    {
        // Calculate VAT on purchases
        $purchaseTransactions = Transaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->where('type', 'purchase')
            ->with('entries.account')
            ->get();

        $vatAmount = 0;
        $netAmount = 0;

        foreach ($purchaseTransactions as $transaction) {
            foreach ($transaction->entries as $entry) {
                if ($entry->account->type === 'expense') {
                    $netAmount += $entry->debit_amount;
                } elseif (stripos($entry->account->name, 'vat') !== false && $entry->account->type === 'asset') {
                    $vatAmount += $entry->debit_amount;
                }
            }
        }

        return ['vat_amount' => $vatAmount, 'net_amount' => $netAmount];
    }

    protected function calculateEUSales(Carbon $startDate, Carbon $endDate): float
    {
        // This would calculate EU sales based on customer location
        // For now, return zero
        return 0;
    }

    protected function checkComplianceIssues(array $report, Carbon $startDate, Carbon $endDate): array
    {
        $issues = [];

        // Check for missing tax filings
        if (empty($report['tax_details'])) {
            $issues[] = [
                'type' => 'missing_data',
                'severity' => 'warning',
                'message' => 'No tax transactions found for the period',
                'recommendation' => 'Verify that all tax transactions have been properly recorded',
            ];
        }

        // Check for unusual tax amounts
        foreach ($report['tax_details'] as $detail) {
            if ($detail['balance'] < 0) {
                $issues[] = [
                    'type' => 'negative_balance',
                    'severity' => 'error',
                    'message' => "Negative tax balance for {$detail['tax_name']}",
                    'recommendation' => 'Review tax calculations and payments',
                ];
            }

            // Check for large variances
            if ($detail['collected'] > 0 && $detail['paid'] > 0) {
                $variance = abs($detail['collected'] - $detail['paid']) / max($detail['collected'], $detail['paid']);
                if ($variance > 0.5) { // 50% variance threshold
                    $issues[] = [
                        'type' => 'large_variance',
                        'severity' => 'warning',
                        'message' => "Large variance between collected and paid amounts for {$detail['tax_name']}",
                        'recommendation' => 'Review tax collection and payment processes',
                    ];
                }
            }
        }

        // Check for overdue payments (simplified check)
        $daysSincePeriodEnd = now()->diffInDays($endDate);
        if ($daysSincePeriodEnd > 30 && $report['summary']['net_tax_liability'] > 0) {
            $issues[] = [
                'type' => 'overdue_payment',
                'severity' => 'error',
                'message' => 'Tax payment may be overdue',
                'recommendation' => 'Check tax payment deadlines and make payment if required',
            ];
        }

        return $issues;
    }
}