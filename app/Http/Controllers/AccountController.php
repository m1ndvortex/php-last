<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AccountController extends Controller
{
    private AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function index(Request $request): JsonResponse
    {
        $query = Account::query();

        if ($request->has('type')) {
            $query->byType($request->type);
        }

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        $accounts = $query->with('parent', 'children')
            ->orderBy('code')
            ->get()
            ->map(function ($account) {
                return [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->localized_name,
                    'type' => $account->type,
                    'subtype' => $account->subtype,
                    'parent_id' => $account->parent_id,
                    'parent_name' => $account->parent?->localized_name,
                    'currency' => $account->currency,
                    'current_balance' => $account->current_balance,
                    'is_active' => $account->is_active,
                    'children_count' => $account->children->count(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $accounts,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:accounts',
            'name' => 'required|string|max:255',
            'name_persian' => 'nullable|string|max:255',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'subtype' => 'required|string',
            'parent_id' => 'nullable|exists:accounts,id',
            'currency' => 'string|size:3',
            'opening_balance' => 'numeric',
            'description' => 'nullable|string',
        ]);

        $account = Account::create($request->all());

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'subtype' => $account->subtype,
                'current_balance' => $account->current_balance,
                'opening_balance' => $account->opening_balance,
                'is_active' => $account->is_active,
            ],
            'message' => 'Account created successfully',
        ], 201);
    }

    public function show(Account $account): JsonResponse
    {
        $account->load('parent', 'children');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->localized_name,
                'type' => $account->type,
                'subtype' => $account->subtype,
                'parent' => $account->parent ? [
                    'id' => $account->parent->id,
                    'name' => $account->parent->localized_name,
                    'code' => $account->parent->code,
                ] : null,
                'children' => $account->children->map(function ($child) {
                    return [
                        'id' => $child->id,
                        'name' => $child->localized_name,
                        'code' => $child->code,
                        'current_balance' => $child->current_balance,
                    ];
                }),
                'currency' => $account->currency,
                'opening_balance' => $account->opening_balance,
                'current_balance' => $account->current_balance,
                'is_active' => $account->is_active,
                'description' => $account->description,
            ],
        ]);
    }

    public function update(Request $request, Account $account): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:accounts,code,' . $account->id,
            'name' => 'required|string|max:255',
            'name_persian' => 'nullable|string|max:255',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'subtype' => 'required|string',
            'parent_id' => 'nullable|exists:accounts,id',
            'currency' => 'string|size:3',
            'opening_balance' => 'numeric',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $account->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $account,
            'message' => 'Account updated successfully',
        ]);
    }

    public function destroy(Account $account): JsonResponse
    {
        if ($account->transactionEntries()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete account with transaction entries',
            ], 422);
        }

        if ($account->children()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete account with child accounts',
            ], 422);
        }

        $account->delete();

        return response()->json([
            'success' => true,
            'message' => 'Account deleted successfully',
        ]);
    }

    public function balance(Account $account, Request $request): JsonResponse
    {
        $asOfDate = $request->has('as_of_date') 
            ? \Carbon\Carbon::parse($request->as_of_date)
            : null;

        $balance = $this->accountingService->getAccountBalance($account, $asOfDate);

        return response()->json([
            'success' => true,
            'data' => [
                'account_id' => $account->id,
                'account_name' => $account->localized_name,
                'account_code' => $account->code,
                'balance' => $balance,
                'as_of_date' => $asOfDate?->toDateString() ?? now()->toDateString(),
            ],
        ]);
    }

    public function ledger(Account $account, Request $request): JsonResponse
    {
        $startDate = $request->has('start_date') 
            ? \Carbon\Carbon::parse($request->start_date)
            : now()->startOfYear();
            
        $endDate = $request->has('end_date')
            ? \Carbon\Carbon::parse($request->end_date)
            : now();

        $ledger = $this->accountingService->getGeneralLedger($account, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'data' => [
                'account' => [
                    'id' => $account->id,
                    'name' => $account->localized_name,
                    'code' => $account->code,
                    'type' => $account->type,
                ],
                'period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                ],
                'entries' => $ledger,
            ],
        ]);
    }

    public function chartOfAccounts(): JsonResponse
    {
        $accounts = Account::active()
            ->whereNull('parent_id')
            ->with(['children' => function ($query) {
                $query->active()->orderBy('code');
            }])
            ->orderBy('code')
            ->get()
            ->map(function ($account) {
                return $this->formatAccountForChart($account);
            });

        return response()->json([
            'success' => true,
            'data' => $accounts,
        ]);
    }

    private function formatAccountForChart(Account $account): array
    {
        return [
            'id' => $account->id,
            'code' => $account->code,
            'name' => $account->localized_name,
            'type' => $account->type,
            'subtype' => $account->subtype,
            'current_balance' => $account->current_balance,
            'children' => $account->children->map(function ($child) {
                return $this->formatAccountForChart($child);
            }),
        ];
    }
}