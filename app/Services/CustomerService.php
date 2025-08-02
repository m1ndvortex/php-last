<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Communication;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CustomerService
{
    protected CommunicationService $communicationService;

    public function __construct(CommunicationService $communicationService)
    {
        $this->communicationService = $communicationService;
    }

    /**
     * Get customers with filtering and pagination.
     *
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getCustomers(array $filters = []): LengthAwarePaginator
    {
        $query = Customer::query()->with(['communications' => function ($q) {
            $q->latest()->limit(5);
        }]);

        // Apply search filter
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Apply customer type filter
        if (!empty($filters['customer_type'])) {
            $query->ofType($filters['customer_type']);
        }

        // Apply CRM stage filter
        if (!empty($filters['crm_stage'])) {
            $query->inStage($filters['crm_stage']);
        }

        // Apply active status filter
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // Apply preferred language filter
        if (!empty($filters['preferred_language'])) {
            $query->where('preferred_language', $filters['preferred_language']);
        }

        // Apply lead source filter
        if (!empty($filters['lead_source'])) {
            $query->where('lead_source', $filters['lead_source']);
        }

        // Apply tags filter
        if (!empty($filters['tags']) && is_array($filters['tags'])) {
            $query->where(function ($q) use ($filters) {
                foreach ($filters['tags'] as $tag) {
                    $q->orWhereJsonContains('tags', $tag);
                }
            });
        }

        // Apply sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        
        $allowedSortFields = ['name', 'email', 'customer_type', 'crm_stage', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $perPage = min($filters['per_page'] ?? 15, 100); // Max 100 items per page
        
        return $query->paginate($perPage);
    }

    /**
     * Create a new customer.
     *
     * @param array $data
     * @return Customer
     */
    public function createCustomer(array $data): Customer
    {
        $customer = Customer::create($data);

        // Log customer creation as communication
        $this->logCustomerActivity($customer, 'Customer created', 'note');

        return $customer;
    }

    /**
     * Get detailed customer information.
     *
     * @param Customer $customer
     * @return array
     */
    public function getCustomerDetails(Customer $customer): array
    {
        $customer->load([
            'communications' => function ($q) {
                $q->with('user')->latest();
            }
        ]);

        return [
            'customer' => $customer,
            'statistics' => [
                'total_invoice_amount' => $customer->getTotalInvoiceAmount(),
                'outstanding_balance' => $customer->getOutstandingBalance(),
                'last_invoice_date' => $customer->getLastInvoiceDate(),
                'total_communications' => $customer->communications->count(),
                'age' => $customer->age,
                'upcoming_birthday' => $customer->hasUpcomingBirthday(),
                'upcoming_anniversary' => $customer->hasUpcomingAnniversary(),
            ]
        ];
    }

    /**
     * Update customer information.
     *
     * @param Customer $customer
     * @param array $data
     * @return Customer
     */
    public function updateCustomer(Customer $customer, array $data): Customer
    {
        $originalData = $customer->toArray();
        $customer->update($data);

        // Log significant changes
        $this->logCustomerChanges($customer, $originalData, $data);

        return $customer->fresh();
    }

    /**
     * Delete a customer (soft delete).
     *
     * @param Customer $customer
     * @return bool
     */
    public function deleteCustomer(Customer $customer): bool
    {
        // Log customer deletion
        $this->logCustomerActivity($customer, 'Customer deleted', 'note');

        return $customer->delete();
    }

    /**
     * Generate customer aging report.
     *
     * @param array $filters
     * @return array
     */
    public function getAgingReport(array $filters = []): array
    {
        $query = Customer::query()->active();

        // Apply filters
        if (!empty($filters['customer_type'])) {
            $query->ofType($filters['customer_type']);
        }

        if (!empty($filters['preferred_language'])) {
            $query->where('preferred_language', $filters['preferred_language']);
        }

        $customers = $query->get();
        $agingData = [];

        foreach ($customers as $customer) {
            $outstandingBalance = $customer->getOutstandingBalance();
            if ($outstandingBalance > 0) {
                $lastInvoiceDate = $customer->getLastInvoiceDate();
                $daysPastDue = $lastInvoiceDate ? now()->diffInDays($lastInvoiceDate) : 0;

                $agingBucket = $this->getAgingBucket($daysPastDue);

                $agingData[] = [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'customer_type' => $customer->customer_type,
                    'outstanding_balance' => $outstandingBalance,
                    'days_past_due' => $daysPastDue,
                    'aging_bucket' => $agingBucket,
                    'last_invoice_date' => $lastInvoiceDate,
                    'preferred_language' => $customer->preferred_language,
                ];
            }
        }

        // Group by aging buckets
        $summary = [
            'current' => ['count' => 0, 'amount' => 0],
            '1-30' => ['count' => 0, 'amount' => 0],
            '31-60' => ['count' => 0, 'amount' => 0],
            '61-90' => ['count' => 0, 'amount' => 0],
            '90+' => ['count' => 0, 'amount' => 0],
        ];

        foreach ($agingData as $item) {
            $bucket = $item['aging_bucket'];
            $summary[$bucket]['count']++;
            $summary[$bucket]['amount'] += $item['outstanding_balance'];
        }

        return [
            'details' => $agingData,
            'summary' => $summary,
            'total_outstanding' => array_sum(array_column($agingData, 'outstanding_balance')),
            'total_customers' => count($agingData),
        ];
    }

    /**
     * Send communication to customer.
     *
     * @param Customer $customer
     * @param string $type
     * @param string $message
     * @param string|null $subject
     * @param array $metadata
     * @return Communication
     */
    public function sendCommunication(
        Customer $customer,
        string $type,
        string $message,
        ?string $subject = null,
        array $metadata = []
    ): Communication {
        $communication = Communication::create([
            'customer_id' => $customer->id,
            'user_id' => Auth::id(),
            'type' => $type,
            'subject' => $subject,
            'message' => $message,
            'metadata' => $metadata,
            'status' => 'draft',
        ]);

        // Send the communication based on type
        $this->communicationService->sendCommunication($communication);

        return $communication;
    }

    /**
     * Get customers with upcoming birthdays.
     *
     * @return Collection
     */
    public function getCustomersWithUpcomingBirthdays(): Collection
    {
        return Customer::active()
            ->withUpcomingBirthdays()
            ->orderBy('birthday')
            ->get();
    }

    /**
     * Get customers with upcoming anniversaries.
     *
     * @return Collection
     */
    public function getCustomersWithUpcomingAnniversaries(): Collection
    {
        return Customer::active()
            ->whereNotNull('anniversary')
            ->get()
            ->filter(function ($customer) {
                return $customer->hasUpcomingAnniversary();
            });
    }

    /**
     * Generate vCard for customer.
     *
     * @param Customer $customer
     * @return string
     */
    public function generateVCard(Customer $customer): string
    {
        $vcard = "BEGIN:VCARD\r\n";
        $vcard .= "VERSION:3.0\r\n";
        $vcard .= "FN:{$customer->name}\r\n";
        $vcard .= "N:{$customer->name};;;;\r\n";
        
        if ($customer->email) {
            $vcard .= "EMAIL:{$customer->email}\r\n";
        }
        
        if ($customer->phone) {
            $vcard .= "TEL:{$customer->phone}\r\n";
        }
        
        if ($customer->address) {
            $vcard .= "ADR:;;{$customer->address};;;;\r\n";
        }
        
        if ($customer->birthday) {
            $vcard .= "BDAY:{$customer->birthday->format('Y-m-d')}\r\n";
        }
        
        $vcard .= "NOTE:Customer Type: {$customer->customer_type}\r\n";
        $vcard .= "END:VCARD\r\n";
        
        return $vcard;
    }

    /**
     * Log customer activity.
     *
     * @param Customer $customer
     * @param string $message
     * @param string $type
     * @return void
     */
    protected function logCustomerActivity(Customer $customer, string $message, string $type = 'note'): void
    {
        Communication::create([
            'customer_id' => $customer->id,
            'user_id' => Auth::id(),
            'type' => $type,
            'message' => $message,
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Log customer changes.
     *
     * @param Customer $customer
     * @param array $originalData
     * @param array $newData
     * @return void
     */
    protected function logCustomerChanges(Customer $customer, array $originalData, array $newData): void
    {
        $changes = [];
        $significantFields = ['name', 'email', 'phone', 'customer_type', 'crm_stage', 'is_active'];

        foreach ($significantFields as $field) {
            if (isset($newData[$field]) && isset($originalData[$field]) && $originalData[$field] !== $newData[$field]) {
                $changes[] = "{$field}: {$originalData[$field]} → {$newData[$field]}";
            }
        }

        if (!empty($changes)) {
            $message = 'Customer updated: ' . implode(', ', $changes);
            $this->logCustomerActivity($customer, $message, 'note');
        }
    }

    /**
     * Get aging bucket for days past due.
     *
     * @param int $daysPastDue
     * @return string
     */
    protected function getAgingBucket(int $daysPastDue): string
    {
        if ($daysPastDue <= 0) {
            return 'current';
        } elseif ($daysPastDue <= 30) {
            return '1-30';
        } elseif ($daysPastDue <= 60) {
            return '31-60';
        } elseif ($daysPastDue <= 90) {
            return '61-90';
        } else {
            return '90+';
        }
    }
}