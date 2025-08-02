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
}