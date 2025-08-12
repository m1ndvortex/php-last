<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'step_order',
        'name',
        'description',
        'approver_type',
        'approver_id',
        'required_approvals',
        'conditions',
        'is_parallel',
        'timeout_hours',
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_parallel' => 'boolean',
        'required_approvals' => 'integer',
        'timeout_hours' => 'integer',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'workflow_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(ApprovalDecision::class, 'step_id');
    }
}