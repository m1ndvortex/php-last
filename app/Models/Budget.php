<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_persian',
        'description',
        'budget_year',
        'start_date',
        'end_date',
        'status',
        'currency',
        'created_by',
        'approved_by',
        'approved_at',
        'parent_budget_id',
        'revision_number',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'budget_year' => 'integer',
        'revision_number' => 'integer',
    ];

    public function budgetLines(): HasMany
    {
        return $this->hasMany(BudgetLine::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function parentBudget(): BelongsTo
    {
        return $this->belongsTo(Budget::class, 'parent_budget_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(Budget::class, 'parent_budget_id');
    }

    public function getLocalizedNameAttribute(): string
    {
        return app()->getLocale() === 'fa' && $this->name_persian 
            ? $this->name_persian 
            : $this->name;
    }
}