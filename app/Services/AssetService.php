<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\AuditLog;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class AssetService
{
    private AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function createAsset(array $data): Asset
    {
        $asset = Asset::create($data);
        
        // Create initial asset transaction
        $this->createAssetPurchaseTransaction($asset);
        
        AuditLog::logActivity($asset, 'created');
        
        return $asset;
    }

    public function updateAsset(Asset $asset, array $data): Asset
    {
        $oldValues = $asset->toArray();
        
        $asset->update($data);
        
        AuditLog::logActivity($asset, 'updated', $oldValues, $asset->toArray());
        
        return $asset;
    }

    public function disposeAsset(Asset $asset, Carbon $disposalDate, float $disposalValue, string $disposalMethod = 'sold'): Transaction
    {
        // Update asset status
        $asset->dispose($disposalDate, $disposalValue);
        
        // Create disposal transaction
        $transaction = $this->createAssetDisposalTransaction($asset, $disposalDate, $disposalValue, $disposalMethod);
        
        AuditLog::logActivity($asset, 'disposed', null, [
            'disposal_date' => $disposalDate->toDateString(),
            'disposal_value' => $disposalValue,
            'disposal_method' => $disposalMethod,
        ]);
        
        return $transaction;
    }

    public function calculateDepreciation(Asset $asset, ?Carbon $asOfDate = null): float
    {
        return $asset->calculateDepreciation($asOfDate);
    }

    public function processDepreciation(?Carbon $asOfDate = null): Collection
    {
        $asOfDate = $asOfDate ?? now();
        $assets = Asset::active()->get();
        $depreciationEntries = collect();
        
        foreach ($assets as $asset) {
            $currentDepreciation = $asset->accumulated_depreciation;
            $newDepreciation = $asset->calculateDepreciation($asOfDate);
            $depreciationExpense = $newDepreciation - $currentDepreciation;
            
            if ($depreciationExpense > 0) {
                // Update asset
                $asset->update([
                    'accumulated_depreciation' => $newDepreciation,
                    'current_value' => max($asset->purchase_cost - $newDepreciation, $asset->salvage_value),
                ]);
                
                // Create depreciation transaction
                $transaction = $this->createDepreciationTransaction($asset, $depreciationExpense, $asOfDate);
                
                $depreciationEntries->push([
                    'asset_id' => $asset->id,
                    'asset_name' => $asset->localized_name,
                    'depreciation_expense' => $depreciationExpense,
                    'accumulated_depreciation' => $newDepreciation,
                    'current_value' => $asset->current_value,
                    'transaction_id' => $transaction->id,
                ]);
            }
        }
        
        return $depreciationEntries;
    }

    public function getAssetRegister(?string $category = null, ?string $status = null): Collection
    {
        $query = Asset::query();
        
        if ($category) {
            $query->byCategory($category);
        }
        
        if ($status) {
            $query->where('status', $status);
        }
        
        return $query->with('costCenter')
            ->orderBy('asset_number')
            ->get()
            ->map(function ($asset) {
                return [
                    'asset_number' => $asset->asset_number,
                    'name' => $asset->localized_name,
                    'category' => $asset->category,
                    'purchase_date' => $asset->purchase_date->toDateString(),
                    'purchase_cost' => $asset->purchase_cost,
                    'accumulated_depreciation' => $asset->accumulated_depreciation,
                    'current_value' => $asset->current_value,
                    'status' => $asset->status,
                    'cost_center' => $asset->costCenter?->localized_name,
                ];
            });
    }

    public function getDepreciationSchedule(Asset $asset): Collection
    {
        $schedule = collect();
        $purchaseDate = Carbon::parse($asset->purchase_date);
        $depreciableAmount = $asset->purchase_cost - $asset->salvage_value;
        
        for ($year = 1; $year <= $asset->useful_life_years; $year++) {
            $yearEndDate = $purchaseDate->copy()->addYears($year)->endOfYear();
            $depreciation = $asset->calculateDepreciation($yearEndDate);
            $yearlyDepreciation = $year == 1 ? $depreciation : $depreciation - $schedule->sum('accumulated_depreciation');
            
            $schedule->push([
                'year' => $year,
                'date' => $yearEndDate->toDateString(),
                'yearly_depreciation' => max($yearlyDepreciation, 0),
                'accumulated_depreciation' => $depreciation,
                'book_value' => max($asset->purchase_cost - $depreciation, $asset->salvage_value),
            ]);
        }
        
        return $schedule;
    }

    private function createAssetPurchaseTransaction(Asset $asset): Transaction
    {
        $assetAccount = $this->getAssetAccount($asset->category);
        $cashAccount = $this->getCashAccount();
        
        return $this->accountingService->createTransaction([
            'description' => 'Asset purchase - ' . $asset->name,
            'description_persian' => 'خرید دارایی - ' . ($asset->name_persian ?? $asset->name),
            'transaction_date' => $asset->purchase_date,
            'type' => 'journal',
            'total_amount' => $asset->purchase_cost,
            'cost_center_id' => $asset->cost_center_id,
            'entries' => [
                [
                    'account_id' => $assetAccount->id,
                    'debit_amount' => $asset->purchase_cost,
                    'credit_amount' => 0,
                    'description' => 'Asset purchase - ' . $asset->name,
                ],
                [
                    'account_id' => $cashAccount->id,
                    'debit_amount' => 0,
                    'credit_amount' => $asset->purchase_cost,
                    'description' => 'Payment for asset - ' . $asset->name,
                ],
            ],
        ]);
    }

    private function createDepreciationTransaction(Asset $asset, float $depreciationExpense, Carbon $date): Transaction
    {
        $depreciationExpenseAccount = $this->getDepreciationExpenseAccount();
        $accumulatedDepreciationAccount = $this->getAccumulatedDepreciationAccount($asset->category);
        
        return $this->accountingService->createTransaction([
            'description' => 'Depreciation expense - ' . $asset->name,
            'description_persian' => 'هزینه استهلاک - ' . ($asset->name_persian ?? $asset->name),
            'transaction_date' => $date,
            'type' => 'journal',
            'total_amount' => $depreciationExpense,
            'cost_center_id' => $asset->cost_center_id,
            'entries' => [
                [
                    'account_id' => $depreciationExpenseAccount->id,
                    'debit_amount' => $depreciationExpense,
                    'credit_amount' => 0,
                    'description' => 'Depreciation expense - ' . $asset->name,
                ],
                [
                    'account_id' => $accumulatedDepreciationAccount->id,
                    'debit_amount' => 0,
                    'credit_amount' => $depreciationExpense,
                    'description' => 'Accumulated depreciation - ' . $asset->name,
                ],
            ],
        ]);
    }

    private function createAssetDisposalTransaction(Asset $asset, Carbon $disposalDate, float $disposalValue, string $disposalMethod): Transaction
    {
        $assetAccount = $this->getAssetAccount($asset->category);
        $accumulatedDepreciationAccount = $this->getAccumulatedDepreciationAccount($asset->category);
        $cashAccount = $this->getCashAccount();
        
        // Calculate gain/loss on disposal
        $bookValue = $asset->current_value;
        $gainLoss = $disposalValue - $bookValue;
        $gainLossAccount = $gainLoss >= 0 
            ? $this->getGainOnDisposalAccount() 
            : $this->getLossOnDisposalAccount();
        
        $entries = [
            // Remove asset from books
            [
                'account_id' => $accumulatedDepreciationAccount->id,
                'debit_amount' => $asset->accumulated_depreciation,
                'credit_amount' => 0,
                'description' => 'Remove accumulated depreciation - ' . $asset->name,
            ],
            [
                'account_id' => $assetAccount->id,
                'debit_amount' => 0,
                'credit_amount' => $asset->purchase_cost,
                'description' => 'Remove asset - ' . $asset->name,
            ],
        ];
        
        // Record cash received (if any)
        if ($disposalValue > 0) {
            $entries[] = [
                'account_id' => $cashAccount->id,
                'debit_amount' => $disposalValue,
                'credit_amount' => 0,
                'description' => 'Cash from asset disposal - ' . $asset->name,
            ];
        }
        
        // Record gain/loss
        if (abs($gainLoss) > 0.01) {
            $entries[] = [
                'account_id' => $gainLossAccount->id,
                'debit_amount' => $gainLoss < 0 ? abs($gainLoss) : 0,
                'credit_amount' => $gainLoss > 0 ? $gainLoss : 0,
                'description' => ($gainLoss >= 0 ? 'Gain' : 'Loss') . ' on disposal - ' . $asset->name,
            ];
        }
        
        return $this->accountingService->createTransaction([
            'description' => 'Asset disposal - ' . $asset->name,
            'description_persian' => 'واگذاری دارایی - ' . ($asset->name_persian ?? $asset->name),
            'transaction_date' => $disposalDate,
            'type' => 'journal',
            'total_amount' => $asset->purchase_cost,
            'cost_center_id' => $asset->cost_center_id,
            'entries' => $entries,
        ]);
    }

    private function getAssetAccount(string $category): Account
    {
        $accountCode = match ($category) {
            'equipment' => '1500',
            'furniture' => '1510',
            'vehicle' => '1520',
            'building' => '1530',
            'software' => '1540',
            default => '1590'
        };
        
        return Account::firstOrCreate([
            'code' => $accountCode,
        ], [
            'name' => ucfirst($category) . ' Assets',
            'name_persian' => 'دارایی‌های ' . $category,
            'type' => 'asset',
            'subtype' => 'fixed_asset',
        ]);
    }

    private function getAccumulatedDepreciationAccount(string $category): Account
    {
        $accountCode = match ($category) {
            'equipment' => '1501',
            'furniture' => '1511',
            'vehicle' => '1521',
            'building' => '1531',
            'software' => '1541',
            default => '1591'
        };
        
        return Account::firstOrCreate([
            'code' => $accountCode,
        ], [
            'name' => 'Accumulated Depreciation - ' . ucfirst($category),
            'name_persian' => 'استهلاک انباشته - ' . $category,
            'type' => 'asset',
            'subtype' => 'fixed_asset',
        ]);
    }

    private function getDepreciationExpenseAccount(): Account
    {
        return Account::firstOrCreate([
            'code' => '6100',
        ], [
            'name' => 'Depreciation Expense',
            'name_persian' => 'هزینه استهلاک',
            'type' => 'expense',
            'subtype' => 'operating_expense',
        ]);
    }

    private function getGainOnDisposalAccount(): Account
    {
        return Account::firstOrCreate([
            'code' => '4200',
        ], [
            'name' => 'Gain on Asset Disposal',
            'name_persian' => 'سود واگذاری دارایی',
            'type' => 'revenue',
            'subtype' => 'other_revenue',
        ]);
    }

    private function getLossOnDisposalAccount(): Account
    {
        return Account::firstOrCreate([
            'code' => '6200',
        ], [
            'name' => 'Loss on Asset Disposal',
            'name_persian' => 'زیان واگذاری دارایی',
            'type' => 'expense',
            'subtype' => 'other_expense',
        ]);
    }

    private function getCashAccount(): Account
    {
        return Account::firstOrCreate([
            'code' => '1010',
        ], [
            'name' => 'Cash',
            'name_persian' => 'نقد',
            'type' => 'asset',
            'subtype' => 'current_asset',
        ]);
    }
}