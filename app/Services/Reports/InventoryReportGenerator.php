<?php

namespace App\Services\Reports;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Category;
use App\Models\Location;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryReportGenerator extends BaseReportGenerator
{
    /**
     * Generate inventory report
     */
    public function generate(): array
    {
        switch ($this->subtype) {
            case 'stock_levels':
                return $this->generateStockLevelsReport();
            case 'movements':
                return $this->generateMovementsReport();
            case 'valuation':
                return $this->generateValuationReport();
            case 'aging':
                return $this->generateAgingReport();
            case 'reorder':
                return $this->generateReorderReport();
            default:
                throw new \InvalidArgumentException("Unknown inventory report subtype: {$this->subtype}");
        }
    }

    /**
     * Generate stock levels report
     */
    protected function generateStockLevelsReport(): array
    {
        $items = $this->getBaseInventoryQuery()
            ->with(['category', 'location'])
            ->get();

        $lowStockItems = $items->filter(function ($item) {
            return $item->quantity <= ($item->reorder_level ?? 0);
        });

        $outOfStockItems = $items->filter(function ($item) {
            return $item->quantity <= 0;
        });

        $categoryBreakdown = $items->groupBy('category.name')
            ->map(function ($categoryItems, $categoryName) {
                return [
                    'category' => $categoryName ?: 'Uncategorized',
                    'total_items' => $categoryItems->count(),
                    'total_quantity' => $categoryItems->sum('quantity'),
                    'total_value' => $categoryItems->sum(function ($item) {
                        return $item->quantity * $item->cost_price;
                    })
                ];
            })->values();

        $locationBreakdown = $items->groupBy('location.name')
            ->map(function ($locationItems, $locationName) {
                return [
                    'location' => $locationName ?: 'No Location',
                    'total_items' => $locationItems->count(),
                    'total_quantity' => $locationItems->sum('quantity'),
                    'total_value' => $locationItems->sum(function ($item) {
                        return $item->quantity * $item->cost_price;
                    })
                ];
            })->values();

        return [
            'id' => $this->reportId,
            'title' => $this->trans('reports.stock_levels_report'),
            'type' => 'inventory',
            'subtype' => 'stock_levels',
            'date_range' => [
                'start' => $this->startDate->toDateString(),
                'end' => $this->endDate->toDateString()
            ],
            'language' => $this->language,
            'generated_at' => now()->toISOString(),
            'summary' => [
                'total_items' => [
                    'value' => $items->count(),
                    'label' => $this->trans('reports.total_items')
                ],
                'total_quantity' => [
                    'value' => $items->sum('quantity'),
                    'label' => $this->trans('reports.total_quantity')
                ],
                'total_value' => [
                    'value' => $items->sum(function ($item) {
                        return $item->quantity * $item->cost_price;
                    }),
                    'formatted' => $this->formatCurrency($items->sum(function ($item) {
                        return $item->quantity * $item->cost_price;
                    })),
                    'label' => $this->trans('reports.total_value')
                ],
                'low_stock_items' => [
                    'value' => $lowStockItems->count(),
                    'label' => $this->trans('reports.low_stock_items')
                ],
                'out_of_stock_items' => [
                    'value' => $outOfStockItems->count(),
                    'label' => $this->trans('reports.out_of_stock_items')
                ]
            ],
            'charts' => [
                'category_breakdown' => $this->generateChartData($categoryBreakdown, 'pie', [
                    'title' => $this->trans('reports.inventory_by_category'),
                    'label_field' => 'category',
                    'value_field' => 'total_value'
                ]),
                'location_breakdown' => $this->generateChartData($locationBreakdown, 'bar', [
                    'title' => $this->trans('reports.inventory_by_location'),
                    'label_field' => 'location',
                    'value_field' => 'total_value',
                    'dataset_label' => $this->trans('reports.value')
                ])
            ],
            'data' => [
                'all_items' => $items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'sku' => $item->sku,
                        'name' => $item->name,
                        'category' => $item->category->name ?? 'Uncategorized',
                        'location' => $item->location->name ?? 'No Location',
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'cost_price' => $item->cost_price,
                        'total_value' => $item->quantity * $item->cost_price,
                        'reorder_level' => $item->reorder_level,
                        'status' => $this->getStockStatus($item),
                        'gold_purity' => $item->gold_purity,
                        'weight' => $item->weight
                    ];
                })->toArray(),
                'low_stock_items' => $lowStockItems->values()->toArray(),
                'out_of_stock_items' => $outOfStockItems->values()->toArray(),
                'category_breakdown' => $categoryBreakdown->toArray(),
                'location_breakdown' => $locationBreakdown->toArray()
            ]
        ];
    }

    /**
     * Generate inventory movements report
     */
    protected function generateMovementsReport(): array
    {
        $movements = InventoryMovement::whereBetween('created_at', [$this->startDate, $this->endDate])
            ->with(['inventoryItem', 'user'])
            ->when(isset($this->filters['item_id']), function ($query) {
                $query->where('inventory_item_id', $this->filters['item_id']);
            })
            ->when(isset($this->filters['movement_type']), function ($query) {
                $query->where('movement_type', $this->filters['movement_type']);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $movementsByType = $movements->groupBy('movement_type')
            ->map(function ($typeMovements, $type) {
                return [
                    'type' => $type,
                    'count' => $typeMovements->count(),
                    'total_quantity' => $typeMovements->sum('quantity_change'),
                    'total_value' => $typeMovements->sum(function ($movement) {
                        return abs($movement->quantity_change) * ($movement->unit_cost ?? 0);
                    })
                ];
            })->values();

        $dailyMovements = $movements->groupBy(function ($movement) {
            return $movement->created_at->format('Y-m-d');
        })->map(function ($dayMovements, $date) {
            return [
                'date' => $date,
                'in_count' => $dayMovements->where('quantity_change', '>', 0)->count(),
                'out_count' => $dayMovements->where('quantity_change', '<', 0)->count(),
                'in_quantity' => $dayMovements->where('quantity_change', '>', 0)->sum('quantity_change'),
                'out_quantity' => abs($dayMovements->where('quantity_change', '<', 0)->sum('quantity_change'))
            ];
        })->values();

        return [
            'id' => $this->reportId,
            'title' => $this->trans('reports.inventory_movements_report'),
            'type' => 'inventory',
            'subtype' => 'movements',
            'date_range' => [
                'start' => $this->startDate->toDateString(),
                'end' => $this->endDate->toDateString()
            ],
            'language' => $this->language,
            'generated_at' => now()->toISOString(),
            'summary' => [
                'total_movements' => [
                    'value' => $movements->count(),
                    'label' => $this->trans('reports.total_movements')
                ],
                'inbound_movements' => [
                    'value' => $movements->where('quantity_change', '>', 0)->count(),
                    'label' => $this->trans('reports.inbound_movements')
                ],
                'outbound_movements' => [
                    'value' => $movements->where('quantity_change', '<', 0)->count(),
                    'label' => $this->trans('reports.outbound_movements')
                ],
                'net_quantity_change' => [
                    'value' => $movements->sum('quantity_change'),
                    'label' => $this->trans('reports.net_quantity_change')
                ]
            ],
            'charts' => [
                'movements_by_type' => $this->generateChartData($movementsByType, 'pie', [
                    'title' => $this->trans('reports.movements_by_type'),
                    'label_field' => 'type',
                    'value_field' => 'count'
                ]),
                'daily_movements' => $this->generateChartData($dailyMovements, 'line', [
                    'title' => $this->trans('reports.daily_movements'),
                    'label_field' => 'date',
                    'value_field' => 'in_count',
                    'dataset_label' => $this->trans('reports.inbound')
                ])
            ],
            'data' => [
                'movements' => $movements->map(function ($movement) {
                    return [
                        'id' => $movement->id,
                        'date' => $this->formatDate($movement->created_at),
                        'item_name' => $movement->inventoryItem->name,
                        'item_sku' => $movement->inventoryItem->sku,
                        'movement_type' => $movement->movement_type,
                        'quantity_change' => $movement->quantity_change,
                        'quantity_before' => $movement->quantity_before,
                        'quantity_after' => $movement->quantity_after,
                        'unit_cost' => $movement->unit_cost,
                        'total_cost' => abs($movement->quantity_change) * ($movement->unit_cost ?? 0),
                        'reference' => $movement->reference,
                        'notes' => $movement->notes,
                        'user' => $movement->user->name ?? 'System'
                    ];
                })->toArray(),
                'movements_by_type' => $movementsByType->toArray(),
                'daily_movements' => $dailyMovements->toArray()
            ]
        ];
    }

    /**
     * Generate inventory valuation report
     */
    protected function generateValuationReport(): array
    {
        $items = $this->getBaseInventoryQuery()
            ->with(['category'])
            ->get();

        $totalCostValue = $items->sum(function ($item) {
            return $item->quantity * $item->cost_price;
        });

        $totalRetailValue = $items->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });

        $potentialProfit = $totalRetailValue - $totalCostValue;
        $profitMargin = $totalRetailValue > 0 ? ($potentialProfit / $totalRetailValue) * 100 : 0;

        $categoryValuation = $items->groupBy('category.name')
            ->map(function ($categoryItems, $categoryName) {
                $costValue = $categoryItems->sum(function ($item) {
                    return $item->quantity * $item->cost_price;
                });
                $retailValue = $categoryItems->sum(function ($item) {
                    return $item->quantity * $item->unit_price;
                });

                return [
                    'category' => $categoryName ?: 'Uncategorized',
                    'item_count' => $categoryItems->count(),
                    'total_quantity' => $categoryItems->sum('quantity'),
                    'cost_value' => $costValue,
                    'retail_value' => $retailValue,
                    'potential_profit' => $retailValue - $costValue,
                    'profit_margin' => $retailValue > 0 ? (($retailValue - $costValue) / $retailValue) * 100 : 0
                ];
            })->values();

        $goldPurityBreakdown = $items->where('gold_purity', '>', 0)
            ->groupBy('gold_purity')
            ->map(function ($purityItems, $purity) {
                return [
                    'purity' => $purity,
                    'item_count' => $purityItems->count(),
                    'total_weight' => $purityItems->sum('weight'),
                    'total_value' => $purityItems->sum(function ($item) {
                        return $item->quantity * $item->cost_price;
                    })
                ];
            })->values();

        return [
            'id' => $this->reportId,
            'title' => $this->trans('reports.inventory_valuation_report'),
            'type' => 'inventory',
            'subtype' => 'valuation',
            'date_range' => [
                'start' => $this->startDate->toDateString(),
                'end' => $this->endDate->toDateString()
            ],
            'language' => $this->language,
            'generated_at' => now()->toISOString(),
            'summary' => [
                'total_cost_value' => [
                    'value' => $totalCostValue,
                    'formatted' => $this->formatCurrency($totalCostValue),
                    'label' => $this->trans('reports.total_cost_value')
                ],
                'total_retail_value' => [
                    'value' => $totalRetailValue,
                    'formatted' => $this->formatCurrency($totalRetailValue),
                    'label' => $this->trans('reports.total_retail_value')
                ],
                'potential_profit' => [
                    'value' => $potentialProfit,
                    'formatted' => $this->formatCurrency($potentialProfit),
                    'label' => $this->trans('reports.potential_profit')
                ],
                'profit_margin' => [
                    'value' => $profitMargin,
                    'formatted' => number_format($profitMargin, 2) . '%',
                    'label' => $this->trans('reports.profit_margin')
                ]
            ],
            'charts' => [
                'category_valuation' => $this->generateChartData($categoryValuation, 'bar', [
                    'title' => $this->trans('reports.valuation_by_category'),
                    'label_field' => 'category',
                    'value_field' => 'retail_value',
                    'dataset_label' => $this->trans('reports.retail_value')
                ]),
                'gold_purity_breakdown' => $this->generateChartData($goldPurityBreakdown, 'pie', [
                    'title' => $this->trans('reports.gold_purity_breakdown'),
                    'label_field' => 'purity',
                    'value_field' => 'total_value'
                ])
            ],
            'data' => [
                'category_valuation' => $categoryValuation->toArray(),
                'gold_purity_breakdown' => $goldPurityBreakdown->toArray(),
                'detailed_items' => $items->map(function ($item) {
                    $costValue = $item->quantity * $item->cost_price;
                    $retailValue = $item->quantity * $item->unit_price;
                    
                    return [
                        'id' => $item->id,
                        'sku' => $item->sku,
                        'name' => $item->name,
                        'category' => $item->category->name ?? 'Uncategorized',
                        'quantity' => $item->quantity,
                        'cost_price' => $item->cost_price,
                        'unit_price' => $item->unit_price,
                        'cost_value' => $costValue,
                        'retail_value' => $retailValue,
                        'potential_profit' => $retailValue - $costValue,
                        'profit_margin' => $retailValue > 0 ? (($retailValue - $costValue) / $retailValue) * 100 : 0,
                        'gold_purity' => $item->gold_purity,
                        'weight' => $item->weight
                    ];
                })->toArray()
            ]
        ];
    }

    /**
     * Generate inventory aging report
     */
    protected function generateAgingReport(): array
    {
        $items = $this->getBaseInventoryQuery()
            ->with(['category'])
            ->get();

        $agingBuckets = [
            '0-30' => ['min' => 0, 'max' => 30, 'items' => collect()],
            '31-60' => ['min' => 31, 'max' => 60, 'items' => collect()],
            '61-90' => ['min' => 61, 'max' => 90, 'items' => collect()],
            '91-180' => ['min' => 91, 'max' => 180, 'items' => collect()],
            '180+' => ['min' => 181, 'max' => null, 'items' => collect()]
        ];

        foreach ($items as $item) {
            $daysOld = $item->created_at->diffInDays(now());
            
            foreach ($agingBuckets as $bucket => &$bucketData) {
                if ($daysOld >= $bucketData['min'] && 
                    ($bucketData['max'] === null || $daysOld <= $bucketData['max'])) {
                    $bucketData['items']->push($item);
                    break;
                }
            }
        }

        $agingSummary = collect($agingBuckets)->map(function ($bucketData, $bucket) {
            $items = $bucketData['items'];
            return [
                'bucket' => $bucket,
                'item_count' => $items->count(),
                'total_quantity' => $items->sum('quantity'),
                'total_value' => $items->sum(function ($item) {
                    return $item->quantity * $item->cost_price;
                })
            ];
        })->values();

        return [
            'id' => $this->reportId,
            'title' => $this->trans('reports.inventory_aging_report'),
            'type' => 'inventory',
            'subtype' => 'aging',
            'date_range' => [
                'start' => $this->startDate->toDateString(),
                'end' => $this->endDate->toDateString()
            ],
            'language' => $this->language,
            'generated_at' => now()->toISOString(),
            'summary' => [
                'total_items' => [
                    'value' => $items->count(),
                    'label' => $this->trans('reports.total_items')
                ],
                'average_age' => [
                    'value' => $items->avg(function ($item) {
                        return $item->created_at->diffInDays(now());
                    }),
                    'formatted' => number_format($items->avg(function ($item) {
                        return $item->created_at->diffInDays(now());
                    }), 0) . ' days',
                    'label' => $this->trans('reports.average_age')
                ]
            ],
            'charts' => [
                'aging_distribution' => $this->generateChartData($agingSummary, 'bar', [
                    'title' => $this->trans('reports.aging_distribution'),
                    'label_field' => 'bucket',
                    'value_field' => 'total_value',
                    'dataset_label' => $this->trans('reports.value')
                ])
            ],
            'data' => [
                'aging_summary' => $agingSummary->toArray(),
                'detailed_aging' => $items->map(function ($item) {
                    $daysOld = $item->created_at->diffInDays(now());
                    return [
                        'id' => $item->id,
                        'sku' => $item->sku,
                        'name' => $item->name,
                        'category' => $item->category->name ?? 'Uncategorized',
                        'quantity' => $item->quantity,
                        'cost_value' => $item->quantity * $item->cost_price,
                        'days_old' => $daysOld,
                        'created_date' => $this->formatDate($item->created_at),
                        'aging_bucket' => $this->getAgingBucket($daysOld)
                    ];
                })->toArray()
            ]
        ];
    }

    /**
     * Generate reorder report
     */
    protected function generateReorderReport(): array
    {
        $items = $this->getBaseInventoryQuery()
            ->with(['category'])
            ->get();

        $reorderItems = $items->filter(function ($item) {
            return $item->quantity <= ($item->reorder_level ?? 0);
        });

        $criticalItems = $items->filter(function ($item) {
            return $item->quantity <= 0;
        });

        $lowStockItems = $items->filter(function ($item) {
            $reorderLevel = $item->reorder_level ?? 0;
            return $item->quantity > 0 && $item->quantity <= $reorderLevel;
        });

        return [
            'id' => $this->reportId,
            'title' => $this->trans('reports.reorder_report'),
            'type' => 'inventory',
            'subtype' => 'reorder',
            'date_range' => [
                'start' => $this->startDate->toDateString(),
                'end' => $this->endDate->toDateString()
            ],
            'language' => $this->language,
            'generated_at' => now()->toISOString(),
            'summary' => [
                'total_reorder_items' => [
                    'value' => $reorderItems->count(),
                    'label' => $this->trans('reports.items_needing_reorder')
                ],
                'critical_items' => [
                    'value' => $criticalItems->count(),
                    'label' => $this->trans('reports.out_of_stock_items')
                ],
                'low_stock_items' => [
                    'value' => $lowStockItems->count(),
                    'label' => $this->trans('reports.low_stock_items')
                ],
                'estimated_reorder_cost' => [
                    'value' => $reorderItems->sum(function ($item) {
                        $reorderQuantity = max(($item->reorder_level ?? 0) * 2 - $item->quantity, 0);
                        return $reorderQuantity * $item->cost_price;
                    }),
                    'formatted' => $this->formatCurrency($reorderItems->sum(function ($item) {
                        $reorderQuantity = max(($item->reorder_level ?? 0) * 2 - $item->quantity, 0);
                        return $reorderQuantity * $item->cost_price;
                    })),
                    'label' => $this->trans('reports.estimated_reorder_cost')
                ]
            ],
            'data' => [
                'reorder_items' => $reorderItems->map(function ($item) {
                    $reorderQuantity = max(($item->reorder_level ?? 0) * 2 - $item->quantity, 0);
                    return [
                        'id' => $item->id,
                        'sku' => $item->sku,
                        'name' => $item->name,
                        'category' => $item->category->name ?? 'Uncategorized',
                        'current_quantity' => $item->quantity,
                        'reorder_level' => $item->reorder_level ?? 0,
                        'suggested_reorder_quantity' => $reorderQuantity,
                        'cost_price' => $item->cost_price,
                        'estimated_cost' => $reorderQuantity * $item->cost_price,
                        'priority' => $item->quantity <= 0 ? 'Critical' : 'Low Stock',
                        'supplier' => $item->supplier ?? 'Not Set'
                    ];
                })->toArray()
            ]
        ];
    }

    /**
     * Get base inventory query with filters
     */
    protected function getBaseInventoryQuery()
    {
        $query = InventoryItem::query();

        if (isset($this->filters['category_id'])) {
            $query->where('category_id', $this->filters['category_id']);
        }

        if (isset($this->filters['location_id'])) {
            $query->where('location_id', $this->filters['location_id']);
        }

        if (isset($this->filters['gold_purity'])) {
            $query->where('gold_purity', $this->filters['gold_purity']);
        }

        if (isset($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        return $query;
    }

    /**
     * Get stock status for an item
     */
    protected function getStockStatus(InventoryItem $item): string
    {
        if ($item->quantity <= 0) {
            return 'Out of Stock';
        } elseif ($item->quantity <= ($item->reorder_level ?? 0)) {
            return 'Low Stock';
        } else {
            return 'In Stock';
        }
    }

    /**
     * Get aging bucket for days old
     */
    protected function getAgingBucket(int $daysOld): string
    {
        if ($daysOld <= 30) return '0-30 days';
        if ($daysOld <= 60) return '31-60 days';
        if ($daysOld <= 90) return '61-90 days';
        if ($daysOld <= 180) return '91-180 days';
        return '180+ days';
    }
}