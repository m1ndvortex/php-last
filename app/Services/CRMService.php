<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Communication;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CRMService
{
    /**
     * Get CRM pipeline data with stage statistics.
     *
     * @return array
     */
    public function getPipelineData(): array
    {
        $stageStats = Customer::select('crm_stage', DB::raw('count(*) as count'))
            ->where('is_active', true)
            ->groupBy('crm_stage')
            ->get()
            ->keyBy('crm_stage');

        $pipeline = [];
        foreach (Customer::CRM_STAGES as $stage => $label) {
            $pipeline[$stage] = [
                'label' => $label,
                'count' => $stageStats->get($stage)?->count ?? 0,
                'customers' => Customer::inStage($stage)
                    ->active()
                    ->with(['communications' => function ($q) {
                        $q->latest()->limit(1);
                    }])
                    ->orderBy('updated_at', 'desc')
                    ->limit(10)
                    ->get()
            ];
        }

        // Calculate conversion rates
        $conversionRates = $this->calculateConversionRates();

        // Get recent stage changes
        $recentChanges = $this->getRecentStageChanges();

        return [
            'pipeline' => $pipeline,
            'conversion_rates' => $conversionRates,
            'recent_changes' => $recentChanges,
            'total_active_customers' => Customer::active()->count(),
            'total_leads' => Customer::inStage('lead')->active()->count(),
            'total_prospects' => Customer::inStage('prospect')->active()->count(),
            'total_customers' => Customer::inStage('customer')->active()->count(),
        ];
    }

    /**
     * Update customer CRM stage.
     *
     * @param Customer $customer
     * @param string $newStage
     * @param string|null $notes
     * @return Customer
     */
    public function updateCustomerStage(Customer $customer, string $newStage, ?string $notes = null): Customer
    {
        $oldStage = $customer->crm_stage;
        
        $customer->update([
            'crm_stage' => $newStage,
            'updated_at' => now(),
        ]);

        // Log the stage change
        $this->logStageChange($customer, $oldStage, $newStage, $notes);

        return $customer->fresh();
    }

    /**
     * Get customers by stage with detailed information.
     *
     * @param string $stage
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCustomersByStage(string $stage, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return Customer::inStage($stage)
            ->active()
            ->with([
                'communications' => function ($q) {
                    $q->latest()->limit(3);
                }
            ])
            ->withCount('communications')
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get CRM analytics and metrics.
     *
     * @param array $dateRange
     * @return array
     */
    public function getCRMAnalytics(array $dateRange = []): array
    {
        $startDate = $dateRange['start'] ?? now()->subDays(30);
        $endDate = $dateRange['end'] ?? now();

        // New customers by stage over time
        $newCustomersByStage = Customer::select(
                'crm_stage',
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count')
            )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('crm_stage', 'date')
            ->orderBy('date')
            ->get()
            ->groupBy('crm_stage');

        // Stage conversion timeline
        $stageChanges = Communication::where('type', 'note')
            ->where('message', 'like', 'CRM stage changed%')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('customer')
            ->orderBy('created_at', 'desc')
            ->get();

        // Customer activity metrics
        $activityMetrics = [
            'total_communications' => Communication::whereBetween('created_at', [$startDate, $endDate])->count(),
            'communications_by_type' => Communication::select('type', DB::raw('count(*) as count'))
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('type')
                ->get()
                ->keyBy('type'),
            'active_customers' => Customer::whereHas('communications', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
            })->count(),
        ];

        // Lead source performance
        $leadSourcePerformance = Customer::select(
                'lead_source',
                'crm_stage',
                DB::raw('count(*) as count')
            )
            ->whereNotNull('lead_source')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('lead_source', 'crm_stage')
            ->get()
            ->groupBy('lead_source');

        return [
            'new_customers_by_stage' => $newCustomersByStage,
            'stage_changes' => $stageChanges,
            'activity_metrics' => $activityMetrics,
            'lead_source_performance' => $leadSourcePerformance,
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
        ];
    }

    /**
     * Get customers requiring follow-up.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCustomersRequiringFollowUp(): \Illuminate\Database\Eloquent\Collection
    {
        $followUpThreshold = now()->subDays(7); // 7 days without communication

        $customers = Customer::active()
            ->whereIn('crm_stage', ['lead', 'prospect'])
            ->with(['communications' => function ($q) {
                $q->latest()->limit(1);
            }])
            ->orderBy('updated_at', 'asc')
            ->get();

        return $customers->filter(function ($customer) use ($followUpThreshold) {
            $lastCommunication = $customer->communications->first();
            
            // No communications at all
            if (!$lastCommunication) {
                return true;
            }
            
            // Last communication is older than threshold
            return $lastCommunication->created_at < $followUpThreshold;
        });
    }

    /**
     * Calculate conversion rates between stages.
     *
     * @return array
     */
    protected function calculateConversionRates(): array
    {
        $totalLeads = Customer::inStage('lead')->count();
        $totalProspects = Customer::inStage('prospect')->count();
        $totalCustomers = Customer::inStage('customer')->count();

        $leadToProspectRate = $totalLeads > 0 ? ($totalProspects / $totalLeads) * 100 : 0;
        $prospectToCustomerRate = $totalProspects > 0 ? ($totalCustomers / $totalProspects) * 100 : 0;
        $leadToCustomerRate = $totalLeads > 0 ? ($totalCustomers / $totalLeads) * 100 : 0;

        return [
            'lead_to_prospect' => round($leadToProspectRate, 2),
            'prospect_to_customer' => round($prospectToCustomerRate, 2),
            'lead_to_customer' => round($leadToCustomerRate, 2),
            'total_leads' => $totalLeads,
            'total_prospects' => $totalProspects,
            'total_customers' => $totalCustomers,
        ];
    }

    /**
     * Get recent stage changes.
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getRecentStageChanges(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return Communication::where('type', 'note')
            ->where('message', 'like', 'CRM stage changed%')
            ->with(['customer', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Log CRM stage change.
     *
     * @param Customer $customer
     * @param string $oldStage
     * @param string $newStage
     * @param string|null $notes
     * @return void
     */
    protected function logStageChange(Customer $customer, string $oldStage, string $newStage, ?string $notes = null): void
    {
        $stageLabels = Customer::CRM_STAGES;
        $oldStageLabel = $stageLabels[$oldStage] ?? $oldStage;
        $newStageLabel = $stageLabels[$newStage] ?? $newStage;

        $message = "CRM stage changed from {$oldStageLabel} to {$newStageLabel}";
        if ($notes) {
            $message .= ". Notes: {$notes}";
        }

        Communication::create([
            'customer_id' => $customer->id,
            'user_id' => Auth::id(),
            'type' => 'note',
            'subject' => 'CRM Stage Change',
            'message' => $message,
            'status' => 'sent',
            'sent_at' => now(),
            'metadata' => [
                'old_stage' => $oldStage,
                'new_stage' => $newStage,
                'change_type' => 'crm_stage_change',
            ],
        ]);
    }

    /**
     * Get CRM dashboard summary.
     *
     * @return array
     */
    public function getDashboardSummary(): array
    {
        $today = now()->startOfDay();
        $thisWeek = now()->startOfWeek();
        $thisMonth = now()->startOfMonth();

        return [
            'today' => [
                'new_leads' => Customer::inStage('lead')->whereDate('created_at', $today)->count(),
                'stage_changes' => Communication::where('message', 'like', 'CRM stage changed%')
                    ->whereDate('created_at', $today)->count(),
                'communications' => Communication::whereDate('created_at', $today)->count(),
            ],
            'this_week' => [
                'new_leads' => Customer::inStage('lead')->where('created_at', '>=', $thisWeek)->count(),
                'conversions' => Communication::where('message', 'like', 'CRM stage changed%')
                    ->where('created_at', '>=', $thisWeek)->count(),
                'active_customers' => Customer::whereHas('communications', function ($q) use ($thisWeek) {
                    $q->where('created_at', '>=', $thisWeek);
                })->count(),
            ],
            'this_month' => [
                'new_customers' => Customer::where('created_at', '>=', $thisMonth)->count(),
                'total_communications' => Communication::where('created_at', '>=', $thisMonth)->count(),
                'follow_ups_needed' => $this->getCustomersRequiringFollowUp()->count(),
            ],
        ];
    }
}