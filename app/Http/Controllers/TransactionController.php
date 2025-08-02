<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class TransactionController extends Controller
{
    private AccountingService $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function index(Request $request): JsonResponse
    {
        $query = Transaction::with(['entries.account', 'creator', 'costCenter']);

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->byDateRange($request->start_date, $request->end_date);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('locked')) {
            $query->where('is_locked', $request->boolean('locked'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('description_persian', 'like', "%{$search}%");
            });
        }

        $transactions = $query->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $transactions->items(),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'description' => 'required|string|max:255',
            'description_persian' => 'nullable|string|max:255',
            'transaction_date' => 'required|date',
            'type' => 'required|in:journal,invoice,payment,adjustment,recurring',
            'source_type' => 'nullable|string',
            'source_id' => 'nullable|integer',
            'total_amount' => 'required|numeric|min:0',
            'currency' => 'string|size:3',
            'exchange_rate' => 'numeric|min:0',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
            'tags' => 'nullable|array',
            'notes' => 'nullable|string',
            'entries' => 'required|array|min:2',
            'entries.*.account_id' => 'required|exists:accounts,id',
            'entries.*.debit_amount' => 'nullable|numeric|min:0',
            'entries.*.credit_amount' => 'nullable|numeric|min:0',
            'entries.*.description' => 'nullable|string',
            'entries.*.description_persian' => 'nullable|string',
        ]);

        // Validate that each entry has either debit or credit amount
        foreach ($request->entries as $entry) {
            $debit = $entry['debit_amount'] ?? 0;
            $credit = $entry['credit_amount'] ?? 0;
            
            if ($debit == 0 && $credit == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Each entry must have either debit or credit amount',
                ], 422);
            }
            
            if ($debit > 0 && $credit > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Each entry cannot have both debit and credit amounts',
                ], 422);
            }
        }

        try {
            $transaction = $this->accountingService->createTransaction($request->all());

            return response()->json([
                'success' => true,
                'data' => $transaction->load(['entries.account', 'creator', 'costCenter']),
                'message' => 'Transaction created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function show(Transaction $transaction): JsonResponse
    {
        $transaction->load(['entries.account', 'creator', 'approver', 'costCenter']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $transaction->id,
                'reference_number' => $transaction->reference_number,
                'description' => $transaction->localized_description,
                'transaction_date' => $transaction->transaction_date->toDateString(),
                'type' => $transaction->type,
                'total_amount' => $transaction->total_amount,
                'currency' => $transaction->currency,
                'exchange_rate' => $transaction->exchange_rate,
                'is_locked' => $transaction->is_locked,
                'is_recurring' => $transaction->is_recurring,
                'cost_center' => $transaction->costCenter ? [
                    'id' => $transaction->costCenter->id,
                    'name' => $transaction->costCenter->localized_name,
                ] : null,
                'tags' => $transaction->tags,
                'notes' => $transaction->notes,
                'creator' => [
                    'id' => $transaction->creator->id,
                    'name' => $transaction->creator->name,
                ],
                'approver' => $transaction->approver ? [
                    'id' => $transaction->approver->id,
                    'name' => $transaction->approver->name,
                    'approved_at' => $transaction->approved_at?->toISOString(),
                ] : null,
                'entries' => $transaction->entries->map(function ($entry) {
                    return [
                        'id' => $entry->id,
                        'account' => [
                            'id' => $entry->account->id,
                            'code' => $entry->account->code,
                            'name' => $entry->account->localized_name,
                        ],
                        'debit_amount' => $entry->debit_amount,
                        'credit_amount' => $entry->credit_amount,
                        'description' => $entry->localized_description,
                    ];
                }),
                'created_at' => $transaction->created_at->toISOString(),
                'updated_at' => $transaction->updated_at->toISOString(),
            ],
        ]);
    }

    public function update(Request $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->is_locked) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update locked transaction',
            ], 422);
        }

        $request->validate([
            'description' => 'required|string|max:255',
            'description_persian' => 'nullable|string|max:255',
            'transaction_date' => 'required|date',
            'total_amount' => 'required|numeric|min:0',
            'currency' => 'string|size:3',
            'exchange_rate' => 'numeric|min:0',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
            'tags' => 'nullable|array',
            'notes' => 'nullable|string',
            'entries' => 'required|array|min:2',
            'entries.*.account_id' => 'required|exists:accounts,id',
            'entries.*.debit_amount' => 'nullable|numeric|min:0',
            'entries.*.credit_amount' => 'nullable|numeric|min:0',
            'entries.*.description' => 'nullable|string',
            'entries.*.description_persian' => 'nullable|string',
        ]);

        try {
            $transaction = $this->accountingService->updateTransaction($transaction, $request->all());

            return response()->json([
                'success' => true,
                'data' => $transaction->load(['entries.account', 'creator', 'costCenter']),
                'message' => 'Transaction updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function destroy(Transaction $transaction): JsonResponse
    {
        try {
            $this->accountingService->deleteTransaction($transaction);

            return response()->json([
                'success' => true,
                'message' => 'Transaction deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function lock(Transaction $transaction): JsonResponse
    {
        if ($transaction->is_locked) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction is already locked',
            ], 422);
        }

        $this->accountingService->lockTransaction($transaction);

        return response()->json([
            'success' => true,
            'message' => 'Transaction locked successfully',
        ]);
    }

    public function unlock(Transaction $transaction): JsonResponse
    {
        if (!$transaction->is_locked) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction is not locked',
            ], 422);
        }

        $this->accountingService->unlockTransaction($transaction);

        return response()->json([
            'success' => true,
            'message' => 'Transaction unlocked successfully',
        ]);
    }

    public function approve(Request $request, Transaction $transaction): JsonResponse
    {
        if ($transaction->approved_by) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction is already approved',
            ], 422);
        }

        $transaction->update([
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transaction approved successfully',
        ]);
    }

    public function duplicate(Transaction $transaction): JsonResponse
    {
        $newTransactionData = [
            'description' => $transaction->description . ' (Copy)',
            'description_persian' => $transaction->description_persian ? $transaction->description_persian . ' (کپی)' : null,
            'transaction_date' => now()->toDateString(),
            'type' => $transaction->type,
            'total_amount' => $transaction->total_amount,
            'currency' => $transaction->currency,
            'exchange_rate' => $transaction->exchange_rate,
            'cost_center_id' => $transaction->cost_center_id,
            'tags' => $transaction->tags,
            'notes' => $transaction->notes,
            'entries' => $transaction->entries->map(function ($entry) {
                return [
                    'account_id' => $entry->account_id,
                    'debit_amount' => $entry->debit_amount,
                    'credit_amount' => $entry->credit_amount,
                    'description' => $entry->description,
                    'description_persian' => $entry->description_persian,
                ];
            })->toArray(),
        ];

        try {
            $newTransaction = $this->accountingService->createTransaction($newTransactionData);

            return response()->json([
                'success' => true,
                'data' => $newTransaction->load(['entries.account', 'creator', 'costCenter']),
                'message' => 'Transaction duplicated successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}