<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        
        $this->actingAs($user, 'sanctum');
    }

    public function test_can_list_accounts()
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

        $response = $this->getJson('/api/accounting/accounts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'code',
                        'name',
                        'type',
                        'subtype',
                        'current_balance',
                        'is_active',
                    ]
                ]
            ])
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_accounts_by_type()
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

        $response = $this->getJson('/api/accounting/accounts?type=asset');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'asset');
    }

    public function test_can_create_account()
    {
        $accountData = [
            'code' => '1010',
            'name' => 'Cash',
            'name_persian' => 'نقد',
            'type' => 'asset',
            'subtype' => 'current_asset',
            'currency' => 'USD',
            'opening_balance' => 1000.00,
            'description' => 'Main cash account',
        ];

        $response = $this->postJson('/api/accounting/accounts', $accountData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'code',
                    'name',
                    'type',
                    'current_balance',
                ],
                'message'
            ]);

        $this->assertDatabaseHas('accounts', [
            'code' => '1010',
            'name' => 'Cash',
            'type' => 'asset',
        ]);
    }

    public function test_cannot_create_account_with_duplicate_code()
    {
        Account::create([
            'code' => '1010',
            'name' => 'Existing Cash',
            'type' => 'asset',
            'subtype' => 'current_asset',
        ]);

        $accountData = [
            'code' => '1010',
            'name' => 'New Cash',
            'type' => 'asset',
            'subtype' => 'current_asset',
        ];

        $response = $this->postJson('/api/accounting/accounts', $accountData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_can_show_account()
    {
        $account = Account::create([
            'code' => '1010',
            'name' => 'Cash',
            'name_persian' => 'نقد',
            'type' => 'asset',
            'subtype' => 'current_asset',
            'opening_balance' => 1000.00,
        ]);

        $response = $this->getJson("/api/accounting/accounts/{$account->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'code',
                    'name',
                    'type',
                    'subtype',
                    'opening_balance',
                    'current_balance',
                    'is_active',
                ]
            ])
            ->assertJsonPath('data.code', '1010')
            ->assertJsonPath('data.name', 'Cash');
    }

    public function test_can_update_account()
    {
        $account = Account::create([
            'code' => '1010',
            'name' => 'Cash',
            'type' => 'asset',
            'subtype' => 'current_asset',
        ]);

        $updateData = [
            'code' => '1010',
            'name' => 'Updated Cash',
            'name_persian' => 'نقد به‌روزشده',
            'type' => 'asset',
            'subtype' => 'current_asset',
            'description' => 'Updated description',
        ];

        $response = $this->putJson("/api/accounting/accounts/{$account->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Cash')
            ->assertJsonPath('message', 'Account updated successfully');

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'name' => 'Updated Cash',
        ]);
    }

    public function test_can_delete_account()
    {
        $account = Account::create([
            'code' => '1010',
            'name' => 'Cash',
            'type' => 'asset',
            'subtype' => 'current_asset',
        ]);

        $response = $this->deleteJson("/api/accounting/accounts/{$account->id}");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Account deleted successfully');

        $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
    }

    public function test_can_get_account_balance()
    {
        $account = Account::create([
            'code' => '1010',
            'name' => 'Cash',
            'type' => 'asset',
            'subtype' => 'current_asset',
            'opening_balance' => 1000.00,
        ]);

        $response = $this->getJson("/api/accounting/accounts/{$account->id}/balance");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'account_id',
                    'account_name',
                    'account_code',
                    'balance',
                    'as_of_date',
                ]
            ])
            ->assertJsonPath('data.balance', 1000);
    }

    public function test_can_get_account_ledger()
    {
        $account = Account::create([
            'code' => '1010',
            'name' => 'Cash',
            'type' => 'asset',
            'subtype' => 'current_asset',
            'opening_balance' => 1000.00,
        ]);

        $response = $this->getJson("/api/accounting/accounts/{$account->id}/ledger");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'account' => [
                        'id',
                        'name',
                        'code',
                        'type',
                    ],
                    'period' => [
                        'start_date',
                        'end_date',
                    ],
                    'entries',
                ]
            ]);
    }

    public function test_can_get_chart_of_accounts()
    {
        $parent = Account::create([
            'code' => '1000',
            'name' => 'Assets',
            'type' => 'asset',
            'subtype' => 'current_asset',
        ]);

        Account::create([
            'code' => '1010',
            'name' => 'Cash',
            'type' => 'asset',
            'subtype' => 'current_asset',
            'parent_id' => $parent->id,
        ]);

        $response = $this->getJson('/api/accounting/chart-of-accounts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'code',
                        'name',
                        'type',
                        'children' => [
                            '*' => [
                                'id',
                                'code',
                                'name',
                                'type',
                            ]
                        ]
                    ]
                ]
            ]);
    }

    public function test_validation_errors_for_invalid_account_data()
    {
        $response = $this->postJson('/api/accounting/accounts', [
            'name' => 'Test Account',
            // Missing required fields
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code', 'type', 'subtype']);
    }
}