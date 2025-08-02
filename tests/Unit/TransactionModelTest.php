<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\TransactionEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_transaction_can_be_created()
    {
        $transaction = Transaction::create([
            'reference_number' => 'TXN-001',
            'description' => 'Test transaction',
            'transaction_date' => now(),
            'type' => 'journal',
            'total_amount' => 1000.00,
            'created_by' => 1,
        ]);

        $this->assertDatabaseHas('transactions', [
            'reference_number' => 'TXN-001',
            'description' => 'Test transaction',
            'total_amount' => 1000.00,
        ]);
    }

    public function test_transaction_generates_reference_number()
    {
        $transaction = Transaction::create([
            'description' => 'Test transaction',
            'transaction_date' => now(),
            'type' => 'journal',
            'total_amount' => 1000.00,
            'created_by' => 1,
        ]);

        $this->assertNotNull($transaction->reference_number);
        $this->assertStringStartsWith('TXN-', $transaction->reference_number);
    }

    public function test_transaction_has_entries_relationship()
    {
        $transaction = Transaction::create([
            'reference_number' => 'TXN-001',
            'description' => 'Test transaction',
            'transaction_date' => now(),
            'type' => 'journal',
            'total_amount' => 1000.00,
            'created_by' => 1,
        ]);

        $account = Account::create([
            'code' => '1010',
            'name' => 'Cash',
            'type' => 'asset',
            'subtype' => 'current_asset',
        ]);

        $entry = TransactionEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $account->id,
            'debit_amount' => 1000.00,
            'credit_amount' => 0,
        ]);

        $this->assertTrue($transaction->entries->contains($entry));
    }

    public function test_transaction_balance_validation()
    {
        $transaction = Transaction::create([
            'reference_number' => 'TXN-001',
            'description' => 'Test transaction',
            'transaction_date' => now(),
            'type' => 'journal',
            'total_amount' => 1000.00,
            'created_by' => 1,
        ]);

        $cashAccount = Account::create([
            'code' => '1010',
            'name' => 'Cash',
            'type' => 'asset',
            'subtype' => 'current_asset',
        ]);

        $revenueAccount = Account::create([
            'code' => '4010',
            'name' => 'Revenue',
            'type' => 'revenue',
            'subtype' => 'operating_revenue',
        ]);

        // Create balanced entries
        TransactionEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $cashAccount->id,
            'debit_amount' => 1000.00,
            'credit_amount' => 0,
        ]);

        TransactionEntry::create([
            'transaction_id' => $transaction->id,
            'account_id' => $revenueAccount->id,
            'debit_amount' => 0,
            'credit_amount' => 1000.00,
        ]);

        $this->assertTrue($transaction->isBalanced());
    }

    public function test_transaction_lock_unlock()
    {
        $transaction = Transaction::create([
            'reference_number' => 'TXN-001',
            'description' => 'Test transaction',
            'transaction_date' => now(),
            'type' => 'journal',
            'total_amount' => 1000.00,
            'is_locked' => false,
            'created_by' => 1,
        ]);

        $this->assertFalse($transaction->is_locked);

        $result = $transaction->lock();
        $this->assertTrue($result);
        $this->assertTrue($transaction->fresh()->is_locked);

        $result = $transaction->unlock();
        $this->assertTrue($result);
        $this->assertFalse($transaction->fresh()->is_locked);
    }

    public function test_transaction_cannot_lock_already_locked()
    {
        $transaction = Transaction::create([
            'reference_number' => 'TXN-001',
            'description' => 'Test transaction',
            'transaction_date' => now(),
            'type' => 'journal',
            'total_amount' => 1000.00,
            'is_locked' => true,
            'created_by' => 1,
        ]);

        $result = $transaction->lock();
        $this->assertFalse($result);
    }

    public function test_transaction_scopes()
    {
        Transaction::create([
            'reference_number' => 'TXN-001',
            'description' => 'Locked transaction',
            'transaction_date' => now(),
            'type' => 'journal',
            'total_amount' => 1000.00,
            'is_locked' => true,
            'created_by' => 1,
        ]);

        Transaction::create([
            'reference_number' => 'TXN-002',
            'description' => 'Unlocked transaction',
            'transaction_date' => now(),
            'type' => 'journal',
            'total_amount' => 500.00,
            'is_locked' => false,
            'created_by' => 1,
        ]);

        $lockedTransactions = Transaction::locked()->get();
        $unlockedTransactions = Transaction::unlocked()->get();

        $this->assertCount(1, $lockedTransactions);
        $this->assertCount(1, $unlockedTransactions);
    }

    public function test_transaction_date_range_scope()
    {
        Transaction::create([
            'reference_number' => 'TXN-001',
            'description' => 'Old transaction',
            'transaction_date' => now()->subDays(10),
            'type' => 'journal',
            'total_amount' => 1000.00,
            'created_by' => 1,
        ]);

        Transaction::create([
            'reference_number' => 'TXN-002',
            'description' => 'Recent transaction',
            'transaction_date' => now(),
            'type' => 'journal',
            'total_amount' => 500.00,
            'created_by' => 1,
        ]);

        $recentTransactions = Transaction::byDateRange(
            now()->subDays(5),
            now()->addDay()
        )->get();

        $this->assertCount(1, $recentTransactions);
        $this->assertEquals('Recent transaction', $recentTransactions->first()->description);
    }

    public function test_localized_description_attribute()
    {
        $transaction = Transaction::create([
            'reference_number' => 'TXN-001',
            'description' => 'Test transaction',
            'description_persian' => 'تراکنش آزمایشی',
            'transaction_date' => now(),
            'type' => 'journal',
            'total_amount' => 1000.00,
            'created_by' => 1,
        ]);

        // Test English locale
        app()->setLocale('en');
        $this->assertEquals('Test transaction', $transaction->localized_description);

        // Test Persian locale
        app()->setLocale('fa');
        $this->assertEquals('تراکنش آزمایشی', $transaction->localized_description);
    }
}