<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\CustomerService;
use App\Services\CRMService;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CustomerController extends Controller
{
    protected CustomerService $customerService;
    protected CRMService $crmService;

    public function __construct(CustomerService $customerService, CRMService $crmService)
    {
        $this->customerService = $customerService;
        $this->crmService = $crmService;
    }

    /**
     * Display a listing of customers with filtering and search.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'search' => 'nullable|string|max:255',
                'customer_type' => 'nullable|in:individual,business',
                'crm_stage' => 'nullable|in:lead,prospect,customer,inactive',
                'is_active' => 'nullable|boolean',
                'preferred_language' => 'nullable|in:en,fa',
                'lead_source' => 'nullable|string|max:100',
                'tags' => 'nullable|array',
                'tags.*' => 'string|max:50',
                'sort_by' => 'nullable|in:name,email,customer_type,crm_stage,created_at,updated_at',
                'sort_direction' => 'nullable|in:asc,desc',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            $filters = $request->only([
                'search',
                'customer_type',
                'crm_stage',
                'is_active',
                'preferred_language',
                'lead_source',
                'tags',
                'sort_by',
                'sort_direction',
                'per_page'
            ]);

            $customers = $this->customerService->getCustomers($filters);

            return response()->json([
                'success' => true,
                'data' => $customers,
                'meta' => [
                    'filters_applied' => array_filter($filters),
                    'total_results' => $customers->total(),
                    'generated_at' => now()->toISOString()
                ],
                'message' => 'Customers retrieved successfully'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid request parameters',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to retrieve customers', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'filters' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve customers',
                'error' => app()->environment('production') ? 'Internal server error' : $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created customer.
     *
     * @param StoreCustomerRequest $request
     * @return JsonResponse
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        try {
            $customer = $this->customerService->createCustomer($request->validated());

            return response()->json([
                'success' => true,
                'data' => $customer->load(['communications']),
                'message' => 'Customer created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified customer.
     *
     * @param Customer $customer
     * @return JsonResponse
     */
    public function show(Customer $customer): JsonResponse
    {
        try {
            $customerData = $this->customerService->getCustomerDetails($customer);

            return response()->json([
                'success' => true,
                'data' => $customerData,
                'message' => 'Customer retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified customer.
     *
     * @param UpdateCustomerRequest $request
     * @param Customer $customer
     * @return JsonResponse
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        try {
            $updatedCustomer = $this->customerService->updateCustomer($customer, $request->validated());

            return response()->json([
                'success' => true,
                'data' => $updatedCustomer->load(['communications']),
                'message' => 'Customer updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified customer.
     *
     * @param Customer $customer
     * @return JsonResponse
     */
    public function destroy(Customer $customer): JsonResponse
    {
        try {
            $this->customerService->deleteCustomer($customer);

            return response()->json([
                'success' => true,
                'message' => 'Customer deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete customer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer aging report.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function agingReport(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['customer_type', 'preferred_language']);
            $agingReport = $this->customerService->getAgingReport($filters);

            return response()->json([
                'success' => true,
                'data' => $agingReport,
                'message' => 'Aging report generated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate aging report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get CRM pipeline data.
     *
     * @return JsonResponse
     */
    public function crmPipeline(): JsonResponse
    {
        try {
            $pipelineData = $this->crmService->getPipelineData();

            return response()->json([
                'success' => true,
                'data' => $pipelineData,
                'message' => 'CRM pipeline data retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve CRM pipeline data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update customer CRM stage.
     *
     * @param Request $request
     * @param Customer $customer
     * @return JsonResponse
     */
    public function updateCrmStage(Request $request, Customer $customer): JsonResponse
    {
        $request->validate([
            'crm_stage' => 'required|in:lead,prospect,customer,inactive',
            'notes' => 'nullable|string|max:1000'
        ]);

        try {
            $updatedCustomer = $this->crmService->updateCustomerStage(
                $customer,
                $request->crm_stage,
                $request->notes
            );

            return response()->json([
                'success' => true,
                'data' => $updatedCustomer,
                'message' => 'Customer CRM stage updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update customer CRM stage',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send communication to customer.
     *
     * @param Request $request
     * @param Customer $customer
     * @return JsonResponse
     */
    public function sendCommunication(Request $request, Customer $customer): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:email,sms,whatsapp,phone,meeting,note',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
            'metadata' => 'nullable|array'
        ]);

        try {
            $communication = $this->customerService->sendCommunication(
                $customer,
                $request->type,
                $request->message,
                $request->subject,
                $request->metadata ?? []
            );

            return response()->json([
                'success' => true,
                'data' => $communication,
                'message' => 'Communication sent successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send communication',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customers with upcoming birthdays.
     *
     * @return JsonResponse
     */
    public function upcomingBirthdays(): JsonResponse
    {
        try {
            $customers = $this->customerService->getCustomersWithUpcomingBirthdays();

            return response()->json([
                'success' => true,
                'data' => $customers,
                'message' => 'Customers with upcoming birthdays retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve customers with upcoming birthdays',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customers with upcoming anniversaries.
     *
     * @return JsonResponse
     */
    public function upcomingAnniversaries(): JsonResponse
    {
        try {
            $customers = $this->customerService->getCustomersWithUpcomingAnniversaries();

            return response()->json([
                'success' => true,
                'data' => $customers,
                'message' => 'Customers with upcoming anniversaries retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve customers with upcoming anniversaries',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export customer data as vCard.
     *
     * @param Customer $customer
     * @return JsonResponse
     */
    public function exportVCard(Customer $customer): JsonResponse
    {
        try {
            $vCardData = $this->customerService->generateVCard($customer);

            return response()->json([
                'success' => true,
                'data' => [
                    'vcard' => $vCardData,
                    'filename' => "contact_{$customer->id}.vcf"
                ],
                'message' => 'vCard generated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate vCard',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}