<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

abstract class BaseReportGenerator
{
    protected string $subtype;
    protected Carbon $startDate;
    protected Carbon $endDate;
    protected array $filters = [];
    protected string $language = 'en';
    protected string $format = 'json';
    protected string $reportId;

    public function __construct()
    {
        $this->reportId = Str::uuid()->toString();
    }

    /**
     * Set report subtype
     */
    public function setSubtype(string $subtype): self
    {
        $this->subtype = $subtype;
        return $this;
    }

    /**
     * Set date range for the report
     */
    public function setDateRange(string $startDate, string $endDate): self
    {
        $this->startDate = Carbon::parse($startDate);
        $this->endDate = Carbon::parse($endDate);
        return $this;
    }

    /**
     * Set filters for the report
     */
    public function setFilters(array $filters): self
    {
        $this->filters = $filters;
        return $this;
    }

    /**
     * Set language for the report
     */
    public function setLanguage(string $language): self
    {
        $this->language = $language;
        return $this;
    }

    /**
     * Set output format
     */
    public function setFormat(string $format): self
    {
        $this->format = $format;
        return $this;
    }

    /**
     * Generate the report
     */
    abstract public function generate(): array;

    /**
     * Get localized text
     */
    protected function trans(string $key, array $replace = []): string
    {
        // Set the app locale temporarily
        $originalLocale = app()->getLocale();
        app()->setLocale($this->language);
        
        $translation = __($key, $replace);
        
        // Restore original locale
        app()->setLocale($originalLocale);
        
        // If translation failed, return a fallback
        if ($translation === $key) {
            // Try to get a simple translation without the reports prefix
            $simpleKey = str_replace('reports.', '', $key);
            $simpleTranslation = ucwords(str_replace('_', ' ', $simpleKey));
            return $simpleTranslation;
        }
        
        return $translation;
    }

    /**
     * Format currency value
     */
    protected function formatCurrency(?float $amount): string
    {
        $amount = $amount ?? 0;
        
        if ($this->language === 'fa') {
            return number_format($amount, 0) . ' ریال';
        }
        return '$' . number_format($amount, 2);
    }

    /**
     * Format date for display
     */
    protected function formatDate(?Carbon $date): ?string
    {
        if ($date === null) {
            return null;
        }
        
        if ($this->language === 'fa') {
            // Persian date formatting would go here
            return $date->format('Y/m/d');
        }
        return $date->format('M d, Y');
    }

    /**
     * Generate chart data
     */
    protected function generateChartData(Collection $data, string $chartType, array $config = []): array
    {
        switch ($chartType) {
            case 'line':
                return $this->generateLineChart($data, $config);
            case 'bar':
                return $this->generateBarChart($data, $config);
            case 'pie':
                return $this->generatePieChart($data, $config);
            case 'area':
                return $this->generateAreaChart($data, $config);
            default:
                return [];
        }
    }

    /**
     * Generate line chart data
     */
    protected function generateLineChart(Collection $data, array $config): array
    {
        return [
            'type' => 'line',
            'title' => $config['title'] ?? '',
            'labels' => $data->pluck($config['label_field'] ?? 'date')->toArray(),
            'datasets' => [
                [
                    'label' => $config['dataset_label'] ?? 'Data',
                    'data' => $data->pluck($config['value_field'] ?? 'value')->toArray(),
                    'borderColor' => $config['color'] ?? '#3B82F6',
                    'backgroundColor' => $config['background_color'] ?? 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.4
                ]
            ]
        ];
    }

    /**
     * Generate bar chart data
     */
    protected function generateBarChart(Collection $data, array $config): array
    {
        return [
            'type' => 'bar',
            'title' => $config['title'] ?? '',
            'labels' => $data->pluck($config['label_field'] ?? 'category')->toArray(),
            'datasets' => [
                [
                    'label' => $config['dataset_label'] ?? 'Data',
                    'data' => $data->pluck($config['value_field'] ?? 'value')->toArray(),
                    'backgroundColor' => $config['colors'] ?? [
                        '#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6'
                    ]
                ]
            ]
        ];
    }

    /**
     * Generate pie chart data
     */
    protected function generatePieChart(Collection $data, array $config): array
    {
        return [
            'type' => 'pie',
            'title' => $config['title'] ?? '',
            'labels' => $data->pluck($config['label_field'] ?? 'category')->toArray(),
            'datasets' => [
                [
                    'data' => $data->pluck($config['value_field'] ?? 'value')->toArray(),
                    'backgroundColor' => $config['colors'] ?? [
                        '#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6',
                        '#EC4899', '#6366F1', '#14B8A6', '#F97316', '#84CC16'
                    ]
                ]
            ]
        ];
    }

    /**
     * Generate area chart data
     */
    protected function generateAreaChart(Collection $data, array $config): array
    {
        return [
            'type' => 'area',
            'title' => $config['title'] ?? '',
            'labels' => $data->pluck($config['label_field'] ?? 'date')->toArray(),
            'datasets' => [
                [
                    'label' => $config['dataset_label'] ?? 'Data',
                    'data' => $data->pluck($config['value_field'] ?? 'value')->toArray(),
                    'borderColor' => $config['color'] ?? '#3B82F6',
                    'backgroundColor' => $config['background_color'] ?? 'rgba(59, 130, 246, 0.2)',
                    'fill' => true,
                    'tension' => 0.4
                ]
            ]
        ];
    }

    /**
     * Calculate percentage change
     */
    protected function calculatePercentageChange(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return (($current - $previous) / $previous) * 100;
    }

    /**
     * Generate summary statistics
     */
    protected function generateSummary(Collection $data, array $fields): array
    {
        $summary = [];
        
        foreach ($fields as $field => $config) {
            $values = $data->pluck($field)->filter();
            
            $summary[$field] = [
                'total' => $values->sum(),
                'average' => $values->avg(),
                'min' => $values->min(),
                'max' => $values->max(),
                'count' => $values->count(),
                'label' => $config['label'] ?? $field,
                'format' => $config['format'] ?? 'number'
            ];
        }
        
        return $summary;
    }
}