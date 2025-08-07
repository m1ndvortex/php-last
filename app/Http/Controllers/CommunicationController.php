<?php

namespace App\Http\Controllers;

use App\Models\Communication;
use App\Models\Customer;
use App\Services\CommunicationService;
use App\Services\WhatsAppService;
use App\Services\SMSService;
use App\Jobs\SendCommunicationJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CommunicationController extends Controller
{
    protected CommunicationService $communicationService;
    protected WhatsAppService $whatsAppService;
    protected SMSService $smsService;

    public function __construct(
        CommunicationService $communicationService,
        WhatsAppService $whatsAppService,
        SMSService $smsService
    ) {
        $this->communicationService = $communicationService;
        $this->whatsAppService = $whatsAppService;
        $this->smsService = $smsService;
    }

    /**
     * Get communications for a customer
     *
     * @param Request $request
     * @param int $customerId
     * @return JsonResponse
     */
    public function index(Request $request, int $customerId): JsonResponse
    {
        try {
            $customer = Customer::findOrFail($customerId);
            
            $communications = Communication::where('customer_id', $customerId)
                ->with(['user'])
                ->when($request->input('type'), function ($query, $type) {
                    return $query->where('type', $type);
                })
                ->when($request->input('status'), function ($query, $status) {
                    return $query->where('status', $status);
                })
                ->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $communications,
                'customer' => $customer
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get communications', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve communications'
            ], 500);
        }
    }

    /**
     * Send a communication
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function send(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'type' => ['required', Rule::in(['whatsapp', 'sms', 'email', 'phone', 'meeting', 'note'])],
                'subject' => 'nullable|string|max:255',
                'message' => 'required|string|max:2000',
                'scheduled_at' => 'nullable|date|after:now'
            ]);

            $customer = Customer::findOrFail($request->input('customer_id'));

            // Create communication record
            $communication = Communication::create([
                'customer_id' => $customer->id,
                'user_id' => auth()->id(),
                'type' => $request->input('type'),
                'subject' => $request->input('subject'),
                'message' => $request->input('message'),
                'status' => 'draft',
                'metadata' => [
                    'scheduled_at' => $request->input('scheduled_at'),
                    'manual_send' => true
                ]
            ]);

            // Send immediately or schedule
            if ($request->input('scheduled_at')) {
                // Schedule for later
                SendCommunicationJob::dispatch(
                    $customer->id,
                    $request->input('type'),
                    $request->input('message'),
                    [
                        'subject' => $request->input('subject'),
                        'communication_id' => $communication->id
                    ]
                )->delay($request->input('scheduled_at'));

                $communication->update(['status' => 'scheduled']);
            } else {
                // Send immediately
                $success = $this->communicationService->sendCommunication($communication);
                
                if (!$success) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to send communication'
                    ], 400);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Communication sent successfully',
                'data' => $communication->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send communication', [
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send communication'
            ], 500);
        }
    }

    /**
     * Send invoice via WhatsApp
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendInvoice(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'invoice_id' => 'required|integer',
                'invoice_number' => 'required|string',
                'total_amount' => 'required|numeric',
                'type' => 'required|in:whatsapp,sms,email'
            ]);

            $customer = Customer::findOrFail($request->input('customer_id'));
            
            $invoiceData = [
                'id' => $request->input('invoice_id'),
                'invoice_number' => $request->input('invoice_number'),
                'total_amount' => $request->input('total_amount'),
                'customer_name' => $customer->name
            ];

            $communication = $this->communicationService->sendInvoice(
                $customer,
                $invoiceData,
                $request->input('type', 'whatsapp')
            );

            return response()->json([
                'success' => true,
                'message' => 'Invoice sent successfully',
                'data' => $communication
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send invoice', [
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send invoice'
            ], 500);
        }
    }

    /**
     * Send birthday reminder
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendBirthdayReminder(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'type' => 'required|in:whatsapp,sms,email'
            ]);

            $customer = Customer::findOrFail($request->input('customer_id'));
            
            $communication = $this->communicationService->sendBirthdayReminder(
                $customer,
                $request->input('type', 'whatsapp')
            );

            if ($communication) {
                return response()->json([
                    'success' => true,
                    'message' => 'Birthday reminder sent successfully',
                    'data' => $communication
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer does not have a birthday set or birthday is not today'
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send birthday reminder', [
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send birthday reminder'
            ], 500);
        }
    }

    /**
     * Send anniversary reminder
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendAnniversaryReminder(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'type' => 'required|in:whatsapp,sms,email'
            ]);

            $customer = Customer::findOrFail($request->input('customer_id'));
            
            $communication = $this->communicationService->sendAnniversaryReminder(
                $customer,
                $request->input('type', 'whatsapp')
            );

            if ($communication) {
                return response()->json([
                    'success' => true,
                    'message' => 'Anniversary reminder sent successfully',
                    'data' => $communication
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer does not have an anniversary set or anniversary is not today'
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send anniversary reminder', [
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send anniversary reminder'
            ], 500);
        }
    }

    /**
     * Get communication statistics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['date_from', 'date_to', 'type', 'customer_id']);
            
            $stats = $this->communicationService->getCommunicationStats($filters);

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get communication stats', [
                'user_id' => auth()->id(),
                'filters' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve communication statistics'
            ], 500);
        }
    }

    /**
     * Get message delivery status
     *
     * @param Request $request
     * @param int $communicationId
     * @return JsonResponse
     */
    public function status(Request $request, int $communicationId): JsonResponse
    {
        try {
            $communication = Communication::findOrFail($communicationId);
            
            $externalId = $communication->metadata['external_id'] ?? null;
            
            if (!$externalId) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'status' => $communication->status,
                        'sent_at' => $communication->sent_at,
                        'delivered_at' => $communication->delivered_at,
                        'read_at' => $communication->read_at
                    ]
                ]);
            }

            // Check status with provider
            $providerStatus = match ($communication->type) {
                'whatsapp' => $this->whatsAppService->getMessageStatus($externalId),
                'sms' => $this->smsService->getMessageStatus($externalId),
                default => ['success' => false, 'error' => 'Status check not supported for this type']
            };

            return response()->json([
                'success' => true,
                'data' => [
                    'local_status' => $communication->status,
                    'provider_status' => $providerStatus,
                    'sent_at' => $communication->sent_at,
                    'delivered_at' => $communication->delivered_at,
                    'read_at' => $communication->read_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get communication status', [
                'communication_id' => $communicationId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve communication status'
            ], 500);
        }
    }

    /**
     * Resend a failed communication
     *
     * @param Request $request
     * @param int $communicationId
     * @return JsonResponse
     */
    public function resend(Request $request, int $communicationId): JsonResponse
    {
        try {
            $communication = Communication::findOrFail($communicationId);
            
            if ($communication->status !== 'failed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only failed communications can be resent'
                ], 400);
            }

            // Reset status and try sending again
            $communication->update([
                'status' => 'draft',
                'metadata' => array_merge($communication->metadata ?? [], [
                    'resent_at' => now()->toISOString(),
                    'resent_by' => auth()->id()
                ])
            ]);

            $success = $this->communicationService->sendCommunication($communication);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Communication resent successfully',
                    'data' => $communication->fresh()
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to resend communication'
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Failed to resend communication', [
                'communication_id' => $communicationId,
                'user_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to resend communication'
            ], 500);
        }
    }
}