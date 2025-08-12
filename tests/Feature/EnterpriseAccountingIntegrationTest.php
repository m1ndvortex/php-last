<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Asset;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\AssetService;
use App\Services\TaxService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnterpriseAccountingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected AccountingService $accountingService;
    protected AssetService $assetService;
    protected TaxService $taxService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->accountingService = app(AccountingService::class);
        $this->assetService = app(AssetService::class);
        $this->taxService = app(TaxService::class);

        // Create test user
        $this->actingAs(User::factory()->create());
    }

    /** @test */
    public function it_can_verify_existing_accounts_structure()
    {
        // Verify that accounts exist in the database
        $accountsCount = Account::count();
        $this->assertGreaterThan(0, $accountsCount);

        // Verify different account types exist
        $this->assertTrue(Account::where('type', 'asset')->exists());
        $this->assertTrue(Account::where('type', 'liability')->exists());
        $this->assertTrue(Account::where('type', 'equity')->exists());
        $this->assertTrue(Account::where('type', 'revenue')->exists());
        $this->assertTrue(Account::where('type', 'expense')->exists());
    }

    /** @test */
    public function it_can_create_basic_transactions()
    {
        $cashAccount = Account::where('type', 'asset')->first();
        $revenueAccount = Account::where('type', 'revenue')->first();

        $this->assertNotNull($cashAccount);
        $this->assertNotNull($revenueAccount);

        $transactionData = [
            'description' => 'Test Transaction',
            'transaction_date' => now(),
            'type' => 'journal',
            'total_amount' => 1000,
            'entries' => [
                [
                    'account_id' => $cashAccount->id,
                    'debit_amount' => 1000,
                    'credit_amount' => 0,
                    'description' => 'Cash received',
                ],
                [
                    'account_id' => $revenueAccount->id,
                    'debit_amount' => 0,
                    'credit_amount' => 1000,
                    'description' => 'Revenue earned',
                ]
            ]
        ];

        $transaction = $this->accountingService->createTransaction($transactionData);

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals('Test Transaction', $transaction->description);
        $this->assertEquals(1000, $transaction->total_amount);
        $this->assertEquals(2, $transaction->entries->count());
        $this->assertTrue($transaction->isBalanced());
    }

    /** @test */
    public function it_can_calculate_account_balances()
    {
        $cashAccount = Account::where('type', 'asset')->first();
        $revenueAccount = Account::where('type', 'revenue')->first();

        // Create a transaction
        $this->accountingService->createTransaction([
            'description' => 'Balance Test',
            'transaction_date' => now(),
            'type' => 'journal',
            'total_amount' => 500,
            'entries' => [
                [
                    'account_id' => $cashAccount->id,
                    'debit_amount' => 500,
                    'credit_amount' => 0,
                ],
                [
                    'account_id' => $revenueAccount->id,
                    'debit_amount' => 0,
                    'credit_amount' => 500,
                ]
            ]
        ]);

        // Test balance calculation
        $cashBalance = $this->accountingService->getAccountBalance($cashAccount);
        $revenueBalance = $this->accountingService->getAccountBalance($revenueAccount);

        // For asset accounts, debit increases balance
        $this->assertGreaterThanOrEqual(500, $cashBalance);
        // For revenue accounts, credit increases balance
        $this->assertGreaterThanOrEqual(500, $revenueBalance);
    }

    /** @test */
    public function it_can_generate_trial_balance()
    {
        $cashAccount = Account::where('type', 'asset')->first();
        $revenueAccount = Account::where('type', 'revenue')->first();

        // Create a transaction
        $this->accountingService->createTransaction([
            'description' => 'Trial Balance Test',
            'transaction_date' => now(),
            'type' => 'journal',
            'total_amount' => 750,
            'entries' => [
                [
                    'account_id' => $cashAccount->id,
                    'debit_amount' => 750,
                    'credit_amount' => 0,
                ],
                [
                    'account_id' => $revenueAccount->id,
                    'debit_amount' => 0,
                    'credit_amount' => 750,
                ]
            ]
        ]);

        $trialBalance = $this->accountingService->getTrialBalance();

        $this->assertNotEmpty($trialBalance);
        
        // Verify trial balance structure
        $firstEntry = $trialBalance->first();
        $this->assertArrayHasKey('account_code', $firstEntry);
        $this->assertArrayHasKey('account_name', $firstEntry);
        $this->assertArrayHasKey('account_type', $firstEntry);
        $this->assertArrayHasKey('debit_balance', $firstEntry);
        $this->assertArrayHasKey('credit_balance', $firstEntry);
        $this->assertArrayHasKey('balance', $firstEntry);
    }

    /** @test */
    public function it_can_create_and_calculate_asset_depreciation()
    {
        $asset = Asset::factory()->create([
            'name' => 'Test Equipment',
            'purchase_cost' => 5000,
            'salvage_value' => 500,
            'useful_life_years' => 5,
            'depreciation_method' => 'straight_line',
            'purchase_date' => now()->subMonths(6),
        ]);

        $depreciationData = $this->assetService->calculateEnhancedDepreciation($asset);

        $this->assertEquals('straight_line', $depreciationData['method']);
        $this->assertEquals(5000, $depreciationData['cost']);
        $this->assertEquals(500, $depreciationData['salvage_value']);
        $this->assertEquals(4500, $depreciationData['depreciable_amount']);
        $this->assertEquals(900, $depreciationData['annual_depreciation']); // (5000-500)/5
        $this->assertEquals(75, $depreciationData['monthly_depreciation']); // 900/12
        $this->assertGreaterThan(0, $depreciationData['accumulated_depreciation']);
    }

    /** @test */
    public function it_can_generate_general_ledger()
    {
        $cashAccount = Account::where('type', 'asset')->first();

        // Create multiple transactions
        for ($i = 1; $i <= 3; $i++) {
            $this->accountingService->createTransaction([
                'description' => "Ledger Test Transaction {$i}",
                'transaction_date' => now()->subDays($i),
                'type' => 'journal',
                'total_amount' => 100 * $i,
                'entries' => [
                    [
                        'account_id' => $cashAccount->id,
                        'debit_amount' => 100 * $i,
                        'credit_amount' => 0,
                    ],
                    [
                        'account_id' => Account::where('type', 'revenue')->first()->id,
                        'debit_amount' => 0,
                        'credit_amount' => 100 * $i,
                    ]
                ]
            ]);
        }

        $generalLedger = $this->accountingService->getGeneralLedger(
            $cashAccount,
            now()->subWeek(),
            now()
        );

        $this->assertNotEmpty($generalLedger);
        $this->assertGreaterThanOrEqual(3, $generalLedger->count());

        // Verify ledger entry structure
        $firstEntry = $generalLedger->first();
        $this->assertArrayHasKey('date', $firstEntry);
        $this->assertArrayHasKey('reference', $firstEntry);
        $this->assertArrayHasKey('description', $firstEntry);
        $this->assertArrayHasKey('debit', $firstEntry);
        $this->assertArrayHasKey('credit', $firstEntry);
        $this->assertArrayHasKey('balance', $firstEntry);
    }

    /** @test */
    public function it_can_handle_transaction_locking()
    {
        $transaction = Transaction::factory()->create([
            'is_locked' => false,
        ]);

        $this->assertFalse($transaction->is_locked);

        // Lock the transaction
        $result = $transaction->lock();
        $this->assertTrue($result);
        $this->assertTrue($transaction->fresh()->is_locked);

        // Try to lock again (should return false)
        $result = $transaction->lock();
        $this->assertFalse($result);

        // Unlock the transaction
        $result = $transaction->unlock();
        $this->assertTrue($result);
        $this->assertFalse($transaction->fresh()->is_locked);
    }

    /** @test */
    public function it_can_generate_reference_numbers()
    {
        $refNumber1 = Transaction::generateReferenceNumber();
        $refNumber2 = Transaction::generateReferenceNumber();

        $this->assertStringStartsWith('TXN-', $refNumber1);
        $this->assertStringStartsWith('TXN-', $refNumber2);
        $this->assertNotEquals($refNumber1, $refNumber2);

        // Verify format: TXN-YYYYMMDD-NNNN
        $this->assertMatchesRegularExpression('/^TXN-\d{8}-\d{4}$/', $refNumber1);
    }

    /** @test */
    public function it_validates_transaction_balance()
    {
        $cashAccount = Account::where('type', 'asset')->first();
        $revenueAccount = Account::where('type', 'revenue')->first();

        // Test balanced transaction
        $balancedData = [
            'description' => 'Balanced Transaction',
            'transaction_date' => now(),
            'type' => 'journal',
            'total_amount' => 1000,
            'entries' => [
                [
                    'account_id' => $cashAccount->id,
                    'debit_amount' => 1000,
                    'credit_amount' => 0,
                ],
                [
                    'account_id' => $revenueAccount->id,
                    'debit_amount' => 0,
                    'credit_amount' => 1000,
                ]
            ]
        ];

        $transaction = $this->accountingService->createTransaction($balancedData);
        $this->assertTrue($transaction->isBalanced());

        // Test unbalanced transaction (should throw exception)
        $unbalancedData = [
            'description' => 'Unbalanced Transaction',
            'transaction_date' => now(),
            'type' => 'journal',
            'total_amount' => 1000,
            'entries' => [
                [
                    'account_id' => $cashAccount->id,
                    'debit_amount' => 1000,
                    'credit_amount' => 0,
                ],
                [
                    'account_id' => $revenueAccount->id,
                    'debit_amount' => 0,
                    'credit_amount' => 800, // Unbalanced
                ]
            ]
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Transaction is not balanced');
        $this->accountingService->createTransaction($unbalancedData);
    }
}