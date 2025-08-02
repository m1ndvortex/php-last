<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Currency;
use App\Models\TaxRate;
use App\Models\CostCenter;
use Illuminate\Database\Seeder;

class AccountingSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCurrencies();
        $this->seedAccounts();
        $this->seedTaxRates();
        $this->seedCostCenters();
    }

    private function seedCurrencies(): void
    {
        $currencies = [
            [
                'code' => 'USD',
                'name' => 'US Dollar',
                'name_persian' => 'دلار آمریکا',
                'symbol' => '$',
                'exchange_rate' => 1.000000,
                'is_base' => true,
                'is_active' => true,
            ],
            [
                'code' => 'IRR',
                'name' => 'Iranian Rial',
                'name_persian' => 'ریال ایران',
                'symbol' => '﷼',
                'exchange_rate' => 42000.000000,
                'is_base' => false,
                'is_active' => true,
            ],
            [
                'code' => 'EUR',
                'name' => 'Euro',
                'name_persian' => 'یورو',
                'symbol' => '€',
                'exchange_rate' => 0.850000,
                'is_base' => false,
                'is_active' => true,
            ],
        ];

        foreach ($currencies as $currency) {
            Currency::create($currency);
        }
    }

    private function seedAccounts(): void
    {
        $accounts = [
            // Assets
            [
                'code' => '1000',
                'name' => 'Assets',
                'name_persian' => 'دارایی‌ها',
                'type' => 'asset',
                'subtype' => 'current_asset',
                'is_system' => true,
            ],
            [
                'code' => '1010',
                'name' => 'Cash',
                'name_persian' => 'نقد',
                'type' => 'asset',
                'subtype' => 'current_asset',
                'parent_id' => null, // Will be set after creation
                'opening_balance' => 10000.00,
            ],
            [
                'code' => '1020',
                'name' => 'Bank Account',
                'name_persian' => 'حساب بانکی',
                'type' => 'asset',
                'subtype' => 'current_asset',
                'opening_balance' => 50000.00,
            ],
            [
                'code' => '1100',
                'name' => 'Accounts Receivable',
                'name_persian' => 'حساب‌های دریافتنی',
                'type' => 'asset',
                'subtype' => 'current_asset',
            ],
            [
                'code' => '1200',
                'name' => 'Inventory',
                'name_persian' => 'موجودی کالا',
                'type' => 'asset',
                'subtype' => 'current_asset',
            ],
            [
                'code' => '1500',
                'name' => 'Equipment',
                'name_persian' => 'تجهیزات',
                'type' => 'asset',
                'subtype' => 'fixed_asset',
            ],
            [
                'code' => '1501',
                'name' => 'Accumulated Depreciation - Equipment',
                'name_persian' => 'استهلاک انباشته - تجهیزات',
                'type' => 'asset',
                'subtype' => 'fixed_asset',
            ],

            // Liabilities
            [
                'code' => '2000',
                'name' => 'Liabilities',
                'name_persian' => 'بدهی‌ها',
                'type' => 'liability',
                'subtype' => 'current_liability',
                'is_system' => true,
            ],
            [
                'code' => '2010',
                'name' => 'Accounts Payable',
                'name_persian' => 'حساب‌های پرداختنی',
                'type' => 'liability',
                'subtype' => 'current_liability',
            ],
            [
                'code' => '2100',
                'name' => 'Sales Tax Payable',
                'name_persian' => 'مالیات فروش پرداختنی',
                'type' => 'liability',
                'subtype' => 'current_liability',
            ],
            [
                'code' => '2200',
                'name' => 'VAT Payable',
                'name_persian' => 'مالیات بر ارزش افزوده پرداختنی',
                'type' => 'liability',
                'subtype' => 'current_liability',
            ],

            // Equity
            [
                'code' => '3000',
                'name' => 'Owner\'s Equity',
                'name_persian' => 'حقوق صاحبان سهام',
                'type' => 'equity',
                'subtype' => 'owner_equity',
                'is_system' => true,
                'opening_balance' => 100000.00,
            ],
            [
                'code' => '3100',
                'name' => 'Retained Earnings',
                'name_persian' => 'سود انباشته',
                'type' => 'equity',
                'subtype' => 'owner_equity',
            ],

            // Revenue
            [
                'code' => '4000',
                'name' => 'Revenue',
                'name_persian' => 'درآمد',
                'type' => 'revenue',
                'subtype' => 'operating_revenue',
                'is_system' => true,
            ],
            [
                'code' => '4010',
                'name' => 'Jewelry Sales',
                'name_persian' => 'فروش جواهرات',
                'type' => 'revenue',
                'subtype' => 'operating_revenue',
            ],
            [
                'code' => '4020',
                'name' => 'Gold Sales',
                'name_persian' => 'فروش طلا',
                'type' => 'revenue',
                'subtype' => 'operating_revenue',
            ],
            [
                'code' => '4100',
                'name' => 'Service Revenue',
                'name_persian' => 'درآمد خدمات',
                'type' => 'revenue',
                'subtype' => 'operating_revenue',
            ],
            [
                'code' => '4200',
                'name' => 'Other Revenue',
                'name_persian' => 'سایر درآمدها',
                'type' => 'revenue',
                'subtype' => 'other_revenue',
            ],

            // Expenses
            [
                'code' => '5000',
                'name' => 'Cost of Goods Sold',
                'name_persian' => 'بهای تمام‌شده کالای فروخته‌شده',
                'type' => 'expense',
                'subtype' => 'operating_expense',
                'is_system' => true,
            ],
            [
                'code' => '5010',
                'name' => 'Gold Purchase Cost',
                'name_persian' => 'هزینه خرید طلا',
                'type' => 'expense',
                'subtype' => 'operating_expense',
            ],
            [
                'code' => '5020',
                'name' => 'Material Cost',
                'name_persian' => 'هزینه مواد اولیه',
                'type' => 'expense',
                'subtype' => 'operating_expense',
            ],
            [
                'code' => '6000',
                'name' => 'Operating Expenses',
                'name_persian' => 'هزینه‌های عملیاتی',
                'type' => 'expense',
                'subtype' => 'operating_expense',
                'is_system' => true,
            ],
            [
                'code' => '6010',
                'name' => 'Rent Expense',
                'name_persian' => 'هزینه اجاره',
                'type' => 'expense',
                'subtype' => 'operating_expense',
            ],
            [
                'code' => '6020',
                'name' => 'Utilities Expense',
                'name_persian' => 'هزینه آب و برق',
                'type' => 'expense',
                'subtype' => 'operating_expense',
            ],
            [
                'code' => '6030',
                'name' => 'Marketing Expense',
                'name_persian' => 'هزینه بازاریابی',
                'type' => 'expense',
                'subtype' => 'operating_expense',
            ],
            [
                'code' => '6100',
                'name' => 'Depreciation Expense',
                'name_persian' => 'هزینه استهلاک',
                'type' => 'expense',
                'subtype' => 'operating_expense',
            ],
            [
                'code' => '6200',
                'name' => 'Other Expenses',
                'name_persian' => 'سایر هزینه‌ها',
                'type' => 'expense',
                'subtype' => 'other_expense',
            ],
        ];

        $createdAccounts = [];
        
        foreach ($accounts as $accountData) {
            $account = Account::create($accountData);
            $createdAccounts[$accountData['code']] = $account;
        }

        // Set parent relationships
        $relationships = [
            '1010' => '1000', // Cash -> Assets
            '1020' => '1000', // Bank -> Assets
            '1100' => '1000', // AR -> Assets
            '1200' => '1000', // Inventory -> Assets
            '1500' => '1000', // Equipment -> Assets
            '1501' => '1000', // Accumulated Depreciation -> Assets
            '2010' => '2000', // AP -> Liabilities
            '2100' => '2000', // Sales Tax -> Liabilities
            '2200' => '2000', // VAT -> Liabilities
            '3100' => '3000', // Retained Earnings -> Equity
            '4010' => '4000', // Jewelry Sales -> Revenue
            '4020' => '4000', // Gold Sales -> Revenue
            '4100' => '4000', // Service Revenue -> Revenue
            '4200' => '4000', // Other Revenue -> Revenue
            '5010' => '5000', // Gold Purchase -> COGS
            '5020' => '5000', // Material Cost -> COGS
            '6010' => '6000', // Rent -> Operating Expenses
            '6020' => '6000', // Utilities -> Operating Expenses
            '6030' => '6000', // Marketing -> Operating Expenses
            '6100' => '6000', // Depreciation -> Operating Expenses
            '6200' => '6000', // Other Expenses -> Operating Expenses
        ];

        foreach ($relationships as $childCode => $parentCode) {
            if (isset($createdAccounts[$childCode]) && isset($createdAccounts[$parentCode])) {
                $createdAccounts[$childCode]->update([
                    'parent_id' => $createdAccounts[$parentCode]->id
                ]);
            }
        }
    }

    private function seedTaxRates(): void
    {
        $taxRates = [
            [
                'name' => 'Sales Tax',
                'name_persian' => 'مالیات فروش',
                'rate' => 0.0825, // 8.25%
                'type' => 'sales',
                'is_compound' => false,
                'is_active' => true,
                'effective_from' => now()->startOfYear(),
                'description' => 'Standard sales tax rate',
            ],
            [
                'name' => 'VAT Standard',
                'name_persian' => 'مالیات بر ارزش افزوده استاندارد',
                'rate' => 0.09, // 9%
                'type' => 'vat',
                'is_compound' => false,
                'is_active' => true,
                'effective_from' => now()->startOfYear(),
                'description' => 'Standard VAT rate',
            ],
            [
                'name' => 'VAT Reduced',
                'name_persian' => 'مالیات بر ارزش افزوده کاهش‌یافته',
                'rate' => 0.045, // 4.5%
                'type' => 'vat',
                'is_compound' => false,
                'is_active' => true,
                'effective_from' => now()->startOfYear(),
                'description' => 'Reduced VAT rate for essential items',
            ],
            [
                'name' => 'Income Tax',
                'name_persian' => 'مالیات بر درآمد',
                'rate' => 0.25, // 25%
                'type' => 'income',
                'is_compound' => false,
                'is_active' => true,
                'effective_from' => now()->startOfYear(),
                'description' => 'Corporate income tax rate',
            ],
        ];

        foreach ($taxRates as $taxRate) {
            TaxRate::create($taxRate);
        }
    }

    private function seedCostCenters(): void
    {
        $costCenters = [
            [
                'code' => 'STORE',
                'name' => 'Store Operations',
                'name_persian' => 'عملیات فروشگاه',
                'description' => 'Main store operations and sales',
                'is_active' => true,
            ],
            [
                'code' => 'WORKSHOP',
                'name' => 'Workshop',
                'name_persian' => 'کارگاه',
                'description' => 'Jewelry manufacturing and repair',
                'is_active' => true,
            ],
            [
                'code' => 'ADMIN',
                'name' => 'Administration',
                'name_persian' => 'اداری',
                'description' => 'Administrative and overhead costs',
                'is_active' => true,
            ],
            [
                'code' => 'MARKETING',
                'name' => 'Marketing',
                'name_persian' => 'بازاریابی',
                'description' => 'Marketing and advertising activities',
                'is_active' => true,
            ],
        ];

        foreach ($costCenters as $costCenter) {
            CostCenter::create($costCenter);
        }
    }
}