<?php

namespace App\Services\Reports;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Communication;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CustomerReportGenerator extends BaseReportGenerator
{
    /**
     * Generate customer report
     */
    public function generate(): array
    {
        switch ($this->subtype) {
            case 'aging':
                return $this->generateAgingReport();
            case 'purchase_history':
                return $this->generatePurchaseHistoryReport();
            case 'communication_log':
                return $this->generateCommunicationLogReport();
            case 'analytics':
                return $this->generateAnalyticsReport();
            default:
                throw new \InvalidArgumentException("Unknown customer report subtype: {$this->subtype}");
        }
    }

    /**
     * Generate customer aging report
     */
    protected function generateAgingReport(): array
    {
        $customers = Customer::with(['invoices' => function ($query) {
            $query->where('status', '!=', 'paid')
                  ->where('due_date', '<=', now());
        }])->get();

        $agingBuckets = [
            'current' => ['min' => -30, 'max' => 0, 'customers' => collect()],
            '1-30' => ['min' => 1, 'max' => 30, 'customers' => collect()],
            '31-60' => ['min' => 31, 'max' => 60, 'customers' => collect()],
            '61-90' => ['min' => 61, 'max' => 90, 'customers' => collect()],
            '90+' => ['min' => 91, 'max' => null, 'customers' => collect()]
        ];

        $customerAgingData = $customers->map(function ($customer) use (&$agingBuckets) {
            $overdueInvoices = $customer->invoices->filter(function ($invoice) {
                return $invoice->due_date < now() && $invoice->status !== 'paid';
            });

            $totalOverdue = $overdueInvoices->sum('total_amount');
            $oldestInvoice = $overdueInvoices->sortBy('due_date')->first();
            $daysOverdue = $oldestInvoice ? $oldestInvoice->due_date->diffInDays(now()) : 0;

            // Categorize customer into aging bucket
            foreach ($agingBuckets as $bucket => &$bucketData) {
                if ($daysOverdue >= $bucketData['min'] && 
                    ($bucketData['max'] === null || $daysOverdue <= $bucketData['max'])) {
                    $bucketData['customers']->push($customer);
                    break;
                }
            }

            return [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_phone' => $customer->phone,
                'customer_email' => $customer->email,
                'total_overdue' => $totalOverdue,
                'overdue_invoices_count' => $overdueInvoices->count(),
                'days_overdue' => $daysOverdue,
                'oldest_invoice_date' => $oldestInvoice ? $this->formatDate($oldestInvoice->due_date) : null,
                'aging_bucket' => $this->getAgingBucket($daysOverdue),
                'last_payment_date' => $this->getLastPaymentDate($customer),
                'credit_limit' => $customer->credit_limit ?? 0,
                'invoices' => $overdueInvoices->map(function ($invoice) {
                    return [
                        'invoice_number' => $invoice->invoice_number,
                        'issue_date' => $this->formatDate($invoice->issue_date),
                        'due_date' => $this->formatDate($invoice->due_date),
                        'amount' => $invoice->total_amount,
                        'days_overdue' => $invoice->due_date->diffInDays(now())
                    ];
                })->toArray()
            ];
        })->filter(function ($customer) {
            return $customer['total_overdue'] > 0;
        });

        $agingSummary = collect($agingBuckets)->map(function ($bucketData, $bucket) {
            $customers = $bucketData['customers'];
            $totalOverdue = $customers->sum(function ($customer) {
                return $customer->invoices->sum('total_amount');
            });

            return [
                'bucket' => $bucket,
                'customer_count' => $customers->count(),
                'total_overdue' => $totalOverdue,
                'percentage' => $customerAgingData->sum('total_overdue') > 0 
                    ? ($totalOverdue / $customerAgingData->sum('total_overdue')) * 100 
                    : 0
            ];
        })->values();

        return [
            'id' => $this->reportId,
            'title' => $this->trans('reports.customer_aging_report'),
            'type' => 'customer',
            'subtype' => 'aging',
            'date_range' => [
                'start' => $this->startDate->toDateString(),
                'end' => $this->endDate->toDateString()
            ],
            'language' => $this->language,
            'generated_at' => now()->toISOString(),
            'summary' => [
                'total_customers_with_overdue' => [
                    'value' => $customerAgingData->count(),
                    'label' => $this->trans('reports.customers_with_overdue')
                ],
                'total_overdue_amount' => [
                    'value' => $customerAgingData->sum('total_overdue'),
                    'formatted' => $this->formatCurrency($customerAgingData->sum('total_overdue')),
                    'label' => $this->trans('reports.total_overdue_amount')
                ],
                'average_days_overdue' => [
                    'value' => $customerAgingData->avg('days_overdue'),
                    'formatted' => number_format($customerAgingData->avg('days_overdue'), 0) . ' days',
                    'label' => $this->trans('reports.average_days_overdue')
                ],
                'oldest_overdue' => [
                    'value' => $customerAgingData->max('days_overdue'),
                    'formatted' => $customerAgingData->max('days_overdue') . ' days',
                    'label' => $this->trans('reports.oldest_overdue')
                ]
            ],
            'charts' => [
                'aging_distribution' => $this->generateChartData($agingSummary, 'pie', [
                    'title' => $this->trans('reports.aging_distribution'),
                    'label_field' => 'bucket',
                    'value_field' => 'total_overdue'
                ]),
                'top_overdue_customers' => $this->generateChartData(
                    $customerAgingData->sortByDesc('total_overdue')->take(10)->values(), 
                    'bar', [
                        'title' => $this->trans('reports.top_overdue_customers'),
                        'label_field' => 'customer_name',
                        'value_field' => 'total_overdue',
                        'dataset_label' => $this->trans('reports.overdue_amount')
                    ]
                )
            ],
            'data' => [
                'customer_aging' => $customerAgingData->values()->toArray(),
                'aging_summary' => $agingSummary->toArray()
            ]
        ];
    }

    /**
     * Generate customer purchase history report
     */
    protected function generatePurchaseHistoryReport(): array
    {
        $customers = Customer::with(['invoices' => function ($query) {
            $query->whereBetween('issue_date', [$this->startDate, $this->endDate])
                  ->with('items.inventoryItem');
        }])->get();

        $customerPurchaseData = $customers->map(function ($customer) {
            $invoices = $customer->invoices;
            $totalPurchases = $invoices->sum('total_amount');
            $totalInvoices = $invoices->count();
            $averageOrderValue = $totalInvoices > 0 ? $totalPurchases / $totalInvoices : 0;
            
            $firstPurchase = $invoices->sortBy('issue_date')->first();
            $lastPurchase = $invoices->sortByDesc('issue_date')->first();
            
            // Calculate purchase frequency
            $daysSinceFirst = $firstPurchase ? $firstPurchase->issue_date->diffInDays(now()) : 0;
            $purchaseFrequency = $daysSinceFirst > 0 ? $totalInvoices / ($daysSinceFirst / 30) : 0; // purchases per month

            // Get top purchased categories
            $categoryPurchases = $invoices->flatMap->items
                ->groupBy('inventoryItem.category.name')
                ->map(function ($items, $category) {
                    return [
                        'category' => $category ?: 'Uncategorized',
                        'quantity' => $items->sum('quantity'),
                        'total_amount' => $items->sum('total_price')
                    ];
                })->sortByDesc('total_amount')->take(5)->values();

            return [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_phone' => $customer->phone,
                'customer_email' => $customer->email,
                'total_purchases' => $totalPurchases,
                'total_invoices' => $totalInvoices,
                'average_order_value' => $averageOrderValue,
                'first_purchase_date' => $firstPurchase ? $this->formatDate($firstPurchase->issue_date) : null,
                'last_purchase_date' => $lastPurchase ? $this->formatDate($lastPurchase->issue_date) : null,
                'purchase_frequency' => $purchaseFrequency,
                'customer_lifetime_value' => $totalPurchases,
                'top_categories' => $categoryPurchases->toArray(),
                'recent_invoices' => $invoices->sortByDesc('issue_date')->take(5)->map(function ($invoice) {
                    return [
                        'invoice_number' => $invoice->invoice_number,
                        'date' => $this->formatDate($invoice->issue_date),
                        'amount' => $invoice->total_amount,
                        'status' => $invoice->status
                    ];
                })->toArray()
            ];
        })->filter(function ($customer) {
            return $customer['total_purchases'] > 0;
        });

        // Customer segmentation
        $segments = $this->segmentCustomers($customerPurchaseData);

        return [
            'id' => $this->reportId,
            'title' => $this->trans('reports.customer_purchase_history'),
            'type' => 'customer',
            'subtype' => 'purchase_history',
            'date_range' => [
                'start' => $this->startDate->toDateString(),
                'end' => $this->endDate->toDateString()
            ],
            'language' => $this->language,
            'generated_at' => now()->toISOString(),
            'summary' => [
                'total_active_customers' => [
                    'value' => $customerPurchaseData->count(),
                    'label' => $this->trans('reports.active_customers')
                ],
                'total_customer_purchases' => [
                    'value' => $customerPurchaseData->sum('total_purchases'),
                    'formatted' => $this->formatCurrency($customerPurchaseData->sum('total_purchases')),
                    'label' => $this->trans('reports.total_purchases')
                ],
                'average_customer_value' => [
                    'value' => $customerPurchaseData->avg('total_purchases'),
                    'formatted' => $this->formatCurrency($customerPurchaseData->avg('total_purchases')),
                    'label' => $this->trans('reports.average_customer_value')
                ],
                'average_order_value' => [
                    'value' => $customerPurchaseData->avg('average_order_value'),
                    'formatted' => $this->formatCurrency($customerPurchaseData->avg('average_order_value')),
                    'label' => $this->trans('reports.average_order_value')
                ]
            ],
            'charts' => [
                'top_customers' => $this->generateChartData(
                    $customerPurchaseData->sortByDesc('total_purchases')->take(10)->values(),
                    'bar', [
                        'title' => $this->trans('reports.top_customers_by_value'),
                        'label_field' => 'customer_name',
                        'value_field' => 'total_purchases',
                        'dataset_label' => $this->trans('reports.purchase_amount')
                    ]
                ),
                'customer_segments' => $this->generateChartData($segments, 'pie', [
                    'title' => $this->trans('reports.customer_segments'),
                    'label_field' => 'segment',
                    'value_field' => 'count'
                ])
            ],
            'data' => [
                'customer_purchases' => $customerPurchaseData->values()->toArray(),
                'customer_segments' => $segments->toArray()
            ]
        ];
    }

    /**
     * Generate communication log report
     */
    protected function generateCommunicationLogReport(): array
    {
        $communications = Communication::whereBetween('created_at', [$this->startDate, $this->endDate])
            ->with(['customer'])
            ->when(isset($this->filters['customer_id']), function ($query) {
                $query->where('customer_id', $this->filters['customer_id']);
            })
            ->when(isset($this->filters['type']), function ($query) {
                $query->where('type', $this->filters['type']);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $communicationsByType = $communications->groupBy('type')
            ->map(function ($typeCommunications, $type) {
                return [
                    'type' => $type,
                    'count' => $typeCommunications->count(),
                    'success_rate' => $typeCommunications->where('status', 'sent')->count() / max($typeCommunications->count(), 1) * 100
                ];
            })->values();

        $communicationsByCustomer = $communications->groupBy('customer_id')
            ->map(function ($customerCommunications) {
                $customer = $customerCommunications->first()->customer;
                return [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'total_communications' => $customerCommunications->count(),
                    'email_count' => $customerCommunications->where('type', 'email')->count(),
                    'sms_count' => $customerCommunications->where('type', 'sms')->count(),
                    'whatsapp_count' => $customerCommunications->where('type', 'whatsapp')->count(),
                    'last_communication' => $this->formatDate($customerCommunications->sortByDesc('created_at')->first()->created_at)
                ];
            })->sortByDesc('total_communications')->values();

        $dailyCommunications = $communications->groupBy(function ($communication) {
            return $communication->created_at->format('Y-m-d');
        })->map(function ($dayCommunications, $date) {
            return [
                'date' => $date,
                'total' => $dayCommunications->count(),
                'email' => $dayCommunications->where('type', 'email')->count(),
                'sms' => $dayCommunications->where('type', 'sms')->count(),
                'whatsapp' => $dayCommunications->where('type', 'whatsapp')->count()
            ];
        })->values();

        return [
            'id' => $this->reportId,
            'title' => $this->trans('reports.communication_log_report'),
            'type' => 'customer',
            'subtype' => 'communication_log',
            'date_range' => [
                'start' => $this->startDate->toDateString(),
                'end' => $this->endDate->toDateString()
            ],
            'language' => $this->language,
            'generated_at' => now()->toISOString(),
            'summary' => [
                'total_communications' => [
                    'value' => $communications->count(),
                    'label' => $this->trans('reports.total_communications')
                ],
                'unique_customers_contacted' => [
                    'value' => $communications->unique('customer_id')->count(),
                    'label' => $this->trans('reports.customers_contacted')
                ],
                'success_rate' => [
                    'value' => $communications->where('status', 'sent')->count() / max($communications->count(), 1) * 100,
                    'formatted' => number_format($communications->where('status', 'sent')->count() / max($communications->count(), 1) * 100, 1) . '%',
                    'label' => $this->trans('reports.overall_success_rate')
                ],
                'average_daily_communications' => [
                    'value' => $dailyCommunications->avg('total'),
                    'formatted' => number_format($dailyCommunications->avg('total'), 1),
                    'label' => $this->trans('reports.average_daily_communications')
                ]
            ],
            'charts' => [
                'communications_by_type' => $this->generateChartData($communicationsByType, 'pie', [
                    'title' => $this->trans('reports.communications_by_type'),
                    'label_field' => 'type',
                    'value_field' => 'count'
                ]),
                'daily_communications' => $this->generateChartData($dailyCommunications, 'line', [
                    'title' => $this->trans('reports.daily_communications'),
                    'label_field' => 'date',
                    'value_field' => 'total',
                    'dataset_label' => $this->trans('reports.communications')
                ])
            ],
            'data' => [
                'all_communications' => $communications->map(function ($communication) {
                    return [
                        'id' => $communication->id,
                        'date' => $this->formatDate($communication->created_at),
                        'customer_name' => $communication->customer->name,
                        'type' => $communication->type,
                        'subject' => $communication->subject,
                        'status' => $communication->status,
                        'sent_at' => $communication->sent_at ? $this->formatDate($communication->sent_at) : null,
                        'opened_at' => $communication->opened_at ? $this->formatDate($communication->opened_at) : null
                    ];
                })->toArray(),
                'communications_by_type' => $communicationsByType->toArray(),
                'communications_by_customer' => $communicationsByCustomer->toArray(),
                'daily_communications' => $dailyCommunications->toArray()
            ]
        ];
    }

    /**
     * Generate customer analytics report
     */
    protected function generateAnalyticsReport(): array
    {
        $customers = Customer::with(['invoices', 'communications'])->get();
        
        $customerAnalytics = $customers->map(function ($customer) {
            $invoices = $customer->invoices->whereBetween('issue_date', [$this->startDate, $this->endDate]);
            $totalSpent = $invoices->sum('total_amount');
            $totalInvoices = $invoices->count();
            
            $firstPurchase = $customer->invoices->sortBy('issue_date')->first();
            $customerAge = $firstPurchase ? $firstPurchase->issue_date->diffInDays(now()) : 0;
            
            $recency = $customer->invoices->sortByDesc('issue_date')->first()?->issue_date->diffInDays(now()) ?? 999;
            $frequency = $customer->invoices->count();
            $monetary = $customer->invoices->sum('total_amount');
            
            // RFM Score calculation (1-5 scale)
            $rfmScore = $this->calculateRFMScore($recency, $frequency, $monetary);
            
            return [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_phone' => $customer->phone,
                'customer_email' => $customer->email,
                'total_spent' => $totalSpent,
                'total_invoices' => $totalInvoices,
                'customer_age_days' => $customerAge,
                'recency_days' => $recency,
                'frequency_score' => $frequency,
                'monetary_value' => $monetary,
                'rfm_score' => $rfmScore,
                'customer_segment' => $this->getCustomerSegment($rfmScore),
                'lifetime_value' => $monetary,
                'average_order_value' => $frequency > 0 ? $monetary / $frequency : 0,
                'communication_count' => $customer->communications->count(),
                'last_communication' => $customer->communications->sortByDesc('created_at')->first()?->created_at
            ];
        });

        $segmentAnalysis = $customerAnalytics->groupBy('customer_segment')
            ->map(function ($segmentCustomers, $segment) {
                return [
                    'segment' => $segment,
                    'customer_count' => $segmentCustomers->count(),
                    'total_value' => $segmentCustomers->sum('monetary_value'),
                    'average_value' => $segmentCustomers->avg('monetary_value'),
                    'percentage' => ($segmentCustomers->count() / max($customerAnalytics->count(), 1)) * 100
                ];
            })->values();

        return [
            'id' => $this->reportId,
            'title' => $this->trans('reports.customer_analytics_report'),
            'type' => 'customer',
            'subtype' => 'analytics',
            'date_range' => [
                'start' => $this->startDate->toDateString(),
                'end' => $this->endDate->toDateString()
            ],
            'language' => $this->language,
            'generated_at' => now()->toISOString(),
            'summary' => [
                'total_customers' => [
                    'value' => $customerAnalytics->count(),
                    'label' => $this->trans('reports.total_customers')
                ],
                'average_customer_value' => [
                    'value' => $customerAnalytics->avg('monetary_value'),
                    'formatted' => $this->formatCurrency($customerAnalytics->avg('monetary_value')),
                    'label' => $this->trans('reports.average_customer_value')
                ],
                'top_segment' => [
                    'value' => $segmentAnalysis->sortByDesc('customer_count')->first()['segment'] ?? 'N/A',
                    'label' => $this->trans('reports.largest_customer_segment')
                ],
                'high_value_customers' => [
                    'value' => $customerAnalytics->where('customer_segment', 'Champions')->count(),
                    'label' => $this->trans('reports.high_value_customers')
                ]
            ],
            'charts' => [
                'segment_distribution' => $this->generateChartData($segmentAnalysis, 'pie', [
                    'title' => $this->trans('reports.customer_segment_distribution'),
                    'label_field' => 'segment',
                    'value_field' => 'customer_count'
                ]),
                'rfm_analysis' => $this->generateChartData(
                    $customerAnalytics->sortByDesc('monetary_value')->take(20)->values(),
                    'bar', [
                        'title' => $this->trans('reports.top_customers_by_value'),
                        'label_field' => 'customer_name',
                        'value_field' => 'monetary_value',
                        'dataset_label' => $this->trans('reports.customer_value')
                    ]
                )
            ],
            'data' => [
                'customer_analytics' => $customerAnalytics->values()->toArray(),
                'segment_analysis' => $segmentAnalysis->toArray()
            ]
        ];
    }

    /**
     * Get aging bucket for days overdue
     */
    protected function getAgingBucket(int $daysOverdue): string
    {
        if ($daysOverdue <= 0) return 'Current';
        if ($daysOverdue <= 30) return '1-30 days';
        if ($daysOverdue <= 60) return '31-60 days';
        if ($daysOverdue <= 90) return '61-90 days';
        return '90+ days';
    }

    /**
     * Get last payment date for customer
     */
    protected function getLastPaymentDate(Customer $customer): ?string
    {
        $lastPaidInvoice = $customer->invoices()
            ->where('status', 'paid')
            ->orderBy('updated_at', 'desc')
            ->first();

        return $lastPaidInvoice ? $this->formatDate($lastPaidInvoice->updated_at) : null;
    }

    /**
     * Segment customers based on purchase behavior
     */
    protected function segmentCustomers(Collection $customers): Collection
    {
        $totalPurchasesMedian = $customers->median('total_purchases');
        $frequencyMedian = $customers->median('total_invoices');

        return collect([
            [
                'segment' => 'High Value',
                'count' => $customers->where('total_purchases', '>', $totalPurchasesMedian)
                    ->where('total_invoices', '>', $frequencyMedian)->count()
            ],
            [
                'segment' => 'Frequent Buyers',
                'count' => $customers->where('total_purchases', '<=', $totalPurchasesMedian)
                    ->where('total_invoices', '>', $frequencyMedian)->count()
            ],
            [
                'segment' => 'Big Spenders',
                'count' => $customers->where('total_purchases', '>', $totalPurchasesMedian)
                    ->where('total_invoices', '<=', $frequencyMedian)->count()
            ],
            [
                'segment' => 'Low Value',
                'count' => $customers->where('total_purchases', '<=', $totalPurchasesMedian)
                    ->where('total_invoices', '<=', $frequencyMedian)->count()
            ]
        ]);
    }

    /**
     * Calculate RFM score
     */
    protected function calculateRFMScore(int $recency, int $frequency, float $monetary): array
    {
        // Simple RFM scoring (1-5 scale)
        $recencyScore = $recency <= 30 ? 5 : ($recency <= 60 ? 4 : ($recency <= 90 ? 3 : ($recency <= 180 ? 2 : 1)));
        $frequencyScore = $frequency >= 10 ? 5 : ($frequency >= 5 ? 4 : ($frequency >= 3 ? 3 : ($frequency >= 2 ? 2 : 1)));
        $monetaryScore = $monetary >= 10000 ? 5 : ($monetary >= 5000 ? 4 : ($monetary >= 2000 ? 3 : ($monetary >= 500 ? 2 : 1)));

        return [
            'recency' => $recencyScore,
            'frequency' => $frequencyScore,
            'monetary' => $monetaryScore,
            'total' => $recencyScore + $frequencyScore + $monetaryScore
        ];
    }

    /**
     * Get customer segment based on RFM score
     */
    protected function getCustomerSegment(array $rfmScore): string
    {
        $total = $rfmScore['total'];
        
        if ($total >= 13) return 'Champions';
        if ($total >= 10) return 'Loyal Customers';
        if ($total >= 8) return 'Potential Loyalists';
        if ($total >= 6) return 'At Risk';
        return 'Lost Customers';
    }
}