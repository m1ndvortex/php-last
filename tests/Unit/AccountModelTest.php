<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user for foreign key constraints
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_account_can_be_created()
    {
        $account = Account::create([
            'code' => '1010',
            'name' => 'Cash',
            'type' => 'asset',
            'subtype' => 'current_asset',
            'opening_balance' => 1000.00,
        ]);

        $this->assertDatabaseHas('accounts', [
            'code' => '1010',
            'name' => 'Cash',
            'type' => 'asset',
            'opening_balance' => 1000.00,
        ]);
    }

    public function test_account_has_parent_child_relationship()
    {
        $parent = Account::create([
            'code' => '1000',
            'name' => 'Assets',
            'type' => 'asset',
            'subtype' => 'current_asset',
        ]);

        $child = Account::create([
            'code' => '1010',
            'name' => 'Cash',
            'type' => 'asset',
            'subtype' => 'current_asset',
            'parent_id' => $parent->id,
        ]);

        $this->assertEquals($parent->id, $child->parent->id);
        $this->assertTrue($parent->children->contains($child));
    }

    public function test_account_balance_calculation()
    {
        $account = Account::create([
            'code' => '1010',
            'name' => 'Cash',
            'type' => 'asset',
            'subtype' => 'current_asset',
            'opening_balance' => 1000.00,
        ]);

        $transaction = Transaction::create([
            'reference_number' => 'TXN-001',
            'description' => 'Test transaction',
            'transaction_date' => now(),
            'type' => 'journal',
            'total_amount' => 500.00,
            'created_by' => 1,
        ]);

        TransactionEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $account->id,
            'debit_amount' => 500.00,
            'credit_amount' => 0,
        ]);

        $account->updateBalance();

        $this->assertEquals(1500.00, $account->fresh()->current_balance);
    }

    public function test_debit_account_identification()
    {
        $assetAccount = Account::create([
            'code' => '1010',
            'name' => 'Cash',
            'type' => 'asset',
            'subtype' => 'current_asset',
        ]);

        $expenseAccount = Account::create([
            'code' => '6010',
            'name' => 'Office Expense',
            'type' => 'expense',
            'subtype' => 'operating_expense',
        ]);

        $this->assertTrue($assetAccount->isDebitAccount());
        $this->assertTrue($expenseAccount->isDebitAccount());
    }

    public function test_credit_account_identification()
    {
        $liabilityAccount = Account::create([
            'code' => '2010',
            'name' => 'Accounts Payable',
            'type' => 'liability',
            'subtype' => 'current_liability',
        ]);

        $revenueAccount = Account::create([
            'code' => '4010',
            'name' => 'Sales Revenue',
            'type' => 'revenue',
            'subtype' => 'operating_revenue',
        ]);

        $this->assertTrue($liabilityAccount->isCreditAccount());
        $this->assertTrue($revenueAccount->isCreditAccount());
    }

    public function test_localized_name_attribute()
    {
        $account = Account::create([
            'code' => '1010',
            'name' => 'Cash',
            'name_persian' => 'نقد',
            'type' => 'asset',
            'subtype' => 'current_asset',
        ]);

        // Test English locale
        app()->setLocale('en');
        $this->assertEquals('Cash', $account->localized_name);

        // Test Persian locale
        app()->setLocale('fa');
        $this->assertEquals('نقد', $account->localized_name);
    }

    public function test_active_scope()
    {
        Account::create([
            'code' => '1010',
            'name' => 'Active Account',
            'type' => 'asset',
            'subtype' => 'current_asset',
            'is_active' => true,
        ]);

        Account::create([
            'code' => '1020',
            'name' => 'Inactive Account',
            'type' => 'asset',
            'subtype' => 'current_asset',
            'is_active' => false,
        ]);

        $activeAccounts = Account::active()->get();
        $this->assertCount(1, $activeAccounts);
        $this->assertEquals('Active Account', $activeAccounts->first()->name);
    }

    public function test_by_type_scope()
    {
        Account::create([
            'code' => '1010',
            'name' => 'Cash',
            'type' => 'asset',
            'subtype' => 'current_asset',
        ]);

        Account::create([
            'code' => '4010',
            'name' => 'Revenue',
            'type' => 'revenue',
            'subtype' => 'operating_revenue',
        ]);

        $assetAccounts = Account::byType('asset')->get();
        $this->assertCount(1, $assetAccounts);
        $this->assertEquals('Cash', $assetAccounts->first()->name);
    }
}