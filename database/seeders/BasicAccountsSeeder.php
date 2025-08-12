<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;

class BasicAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['code' => '1210', 'name' => 'Trade Receivables', 'type' => 'asset', 'subtype' => 'current_asset'],
            ['code' => '4110', 'name' => 'Gold Jewelry Sales', 'type' => 'revenue', 'subtype' => 'operating_revenue'],
            ['code' => '2310', 'name' => 'Sales Tax Payable', 'type' => 'liability', 'subtype' => 'current_liability'],
            ['code' => '1350', 'name' => 'Finished Goods', 'type' => 'asset', 'subtype' => 'current_asset'],
            ['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => 'expense', 'subtype' => 'operating_expense'],
            ['code' => '6200', 'name' => 'Administrative Expenses', 'type' => 'expense', 'subtype' => 'operating_expense'],
        ];

        foreach ($accounts as $accountData) {
            Account::firstOrCreate(
                ['code' => $accountData['code']], 
                array_merge($accountData, [
                    'is_active' => true,
                    'currency' => 'USD',
                    'opening_balance' => 0,
                    'current_balance' => 0,
                    'description' => "Auto-generated account for {$accountData['name']}",
                ])
            );
        }
    }
}