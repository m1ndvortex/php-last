<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'budget_id',
        'account_id',
        'account_code',
        'account_name',
        'category',
        'subcategory',
        'cost_center_id',
        'department_id',
        'january',
        'february',
        'march',
        'april',
        'may',
        'june',
        'july',
        'august',
        'september',
        'october',
        'november',
        'december',
        'total_budget',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'january' => 'decimal:2',
        'february' => 'decimal:2',
        'march' => 'decimal:2',
        'april' => 'decimal:2',
        'may' => 'decimal:2',
        'june' => 'decimal:2',
        'july' => 'decimal:2',
        'august' => 'decimal:2',
        'september' => 'decimal:2',
        'october' => 'decimal:2',
        'november' => 'decimal:2',
        'december' => 'decimal:2',
        'total_budget' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }

    public function getMonthlyAmounts(): array
    {
        return [
            'january' => $this->january,
            'february' => $this->february,
            'march' => $this->march,
            'april' => $this->april,
            'may' => $this->may,
            'june' => $this->june,
            'july' => $this->july,
            'august' => $this->august,
            'september' => $this->september,
            'october' => $this->october,
            'november' => $this->november,
            'december' => $this->december,
        ];
    }
}