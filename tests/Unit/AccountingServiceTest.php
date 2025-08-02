<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingServiceTest extends TestCase
{
    use RefreshDatabase;

    private AccountingService $accountingService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->accountingService = new AccountingService();
        
        // Create a test user
        User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        
        $this->actingAs(User::first());
    }

    public function test_create_transaction()
    {
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

        $transactionData = [
            'description' => 'Test sale',
            'transaction_date' => now()->toDateString(),
            'type' => 'journal',
            'total_amount' => 1000.00,
            'entries' => [
                [
                    'account_id' => $cashAccount->id,
                    'debit_amount' => 1000.00,
                    'credit_amount' => 0,
                ],
                [
                    'account_id' => $revenueAccount->id,
                    'debit_amount' => 0,
                    'credit_amount' => 1000.00,
                ],
            ],
        ];

        $transaction = $this->accountingService->createTransaction($transactionData);

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals('Test sale', $transaction->description);
        $this->assertCount(2, $transaction->entries);
        $this->assertTrue($transaction->isBalanced());
    }

    public function test_create_unbalanced_transaction_throws_exception()
    {
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

        $transactionData = [
            'description' => 'Unbalanced transaction',
            'transaction_date' => now()->toDateString(),
            'type' => 'journal',
            'total_amount' => 1000.00,
            'entries' => [
                [
                    'account_id' => $cashAccount->id,
                    'debit_amount' => 1000.00,
                    'credit_amount' => 0,
                ],
                [
                    'account_id' => $revenueAccount->id,
                    'debit_amount' => 0,
                    'credit_amount' => 500.00, // Unbalanced
                ],
            ],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Transaction is not balanced');

        $this->accountingService->createTransaction($transactionData);
    }

    public function test_update_transaction()
    {
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

        $transaction = Transaction::create([
            'reference_number' => 'TXN-001',
            'description' => 'Original description',
            'transaction_date' => now(),
            'type' => 'journal',
            'total_amount' => 1000.00,
            'created_by' => 1,
        ]);

        $updateData = [
            'description' => 'Updated description',
            'transaction_date' => now()->toDateString(),
            'total_amount' => 1500.00,
            'entries' => [
                [
                    'account_id' => $cashAccount->id,
                    'debit_amount' => 1500.00,
                    'credit_amount' => 0,
                ],
                [
                    'account_id' => $revenueAccount->id,
                    'debit_amount' => 0,
                    'credit_amount' => 1500.00,
                ],
            ],
        ];

        $updatedTransaction = $this->accountingService->updateTransaction($transaction, $updateData);

        $this->assertEquals('Updated description', $updatedTransaction->description);
        $this->assertEquals(1500.00, $updatedTransaction->total_amount);
    }

    public function test_cannot_update_locked_transaction()
    {
        $transaction = Transaction::create([
            'reference_number' => 'TXN-001',
            'description' => 'Locked transaction',
            'transaction_date' => now(),
            'type' => 'journal',
            'total_amount' => 1000.00,
            'is_locked' => true,
            'created_by' => 1,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot update locked transaction');

        $this->accountingService->updateTransaction($transaction, [
            'description' => 'Updated description',
        ]);
    }

    public function test_lock_unlock_transaction()
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

        $result = $this->accountingService->lockTransaction($transaction);
        $this->assertTrue($result);
        $this->assertTrue($transaction->fresh()->is_locked);

        $result = $this->accountingService->unlockTransaction($transaction->fresh());
        $this->assertTrue($result);
        $this->assertFalse($transaction->fresh()->is_locked);
    }

    public function test_delete_transaction()
    {
        $transaction = Transaction::create([
            'reference_number' => 'TXN-001',
            'description' => 'Test transaction',
            'transaction_date' => now(),
            'type' => 'journal',
            'total_amount' => 1000.00,
            'created_by' => 1,
        ]);

        $transactionId = $transaction->id;

        $result = $this->accountingService->deleteTransaction($transaction);
        $this->assertTrue($result);

        $this->assertDatabaseMissing('transactions', ['id' => $transactionId]);
    }

    public function test_cannot_delete_locked_transaction()
    {
        $transaction = Transaction::create([
            'reference_number' => 'TXN-001',
            'description' => 'Locked transaction',
            'transaction_date' => now(),
            'type' => 'journal',
            'total_amount' => 1000.00,
            'is_locked' => true,
            'created_by' => 1,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot delete locked transaction');

        $this->accountingService->deleteTransaction($transaction);
    }

    public function test_get_account_balance()
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

        $transaction->entries()->create([
            'account_id' => $account->id,
            'debit_amount' => 500.00,
            'credit_amount' => 0,
        ]);

        $balance = $this->accountingService->getAccountBalance($account);
        $this->assertEquals(1500.00, $balance);
    }

    public function test_get_trial_balance()
    {
        $cashAccount = Account::create([
            'code' => '1010',
            'name' => 'Cash',
            'type' => 'asset',
            'subtype' => 'current_asset',
            'opening_balance' => 1000.00,
        ]);

        $revenueAccount = Account::create([
            'code' => '4010',
            'name' => 'Revenue',
            'type' => 'revenue',
            'subtype' => 'operating_revenue',
            'opening_balance' => 0,
        ]);

        $transaction = Transaction::create([
            'reference_number' => 'TXN-001',
            'description' => 'Test sale',
            'transaction_date' => now(),
            'type' => 'journal',
            'total_amount' => 500.00,
            'created_by' => 1,
        ]);

        $transaction->entries()->createMany([
            [
                'account_id' => $cashAccount->id,
                'debit_amount' => 500.00,
                'credit_amount' => 0,
            ],
            [
                'account_id' => $revenueAccount->id,
                'debit_amount' => 0,
                'credit_amount' => 500.00,
            ],
        ]);

        $trialBalance = $this->accountingService->getTrialBalance();

        $this->assertCount(2, $trialBalance);
        
        $cashBalance = $trialBalance->where('account_code', '1010')->first();
        $this->assertEquals(1500.00, $cashBalance['debit_balance']);
        
        $revenueBalance = $trialBalance->where('account_code', '4010')->first();
        $this->assertEquals(500.00, $revenueBalance['credit_balance']);
    }

    public function test_get_general_ledger()
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

        $transaction->entries()->create([
            'account_id' => $account->id,
            'debit_amount' => 500.00,
            'credit_amount' => 0,
        ]);

        $ledger = $this->accountingService->getGeneralLedger($account);

        $this->assertCount(1, $ledger);
        
        $entry = $ledger->first();
        $this->assertEquals('TXN-001', $entry['reference']);
        $this->assertEquals(500.00, $entry['debit']);
        $this->assertEquals(1500.00, $entry['balance']);
    }
}