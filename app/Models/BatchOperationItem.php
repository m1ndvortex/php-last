<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BatchOperationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_operation_id',
        'reference_type',
        'reference_id',
        'customer_id',
        'status',
        'error_message',
        'data',
        'processed_at'
    ];

    protected $casts = [
        'data' => 'array',
        'processed_at' => 'datetime'
    ];

    /**
     * Get the batch operation this item belongs to
     */
    public function batchOperation(): BelongsTo
    {
        return $this->belongsTo(BatchOperation::class);
    }

    /**
     * Get the customer associated with this item
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the referenced model (polymorphic)
     */
    public function reference(): MorphTo
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    /**
     * Get the invoice if reference type is invoice
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'reference_id')
            ->where('reference_type', 'invoice');
    }

    /**
     * Check if item was processed successfully
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if item failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get data value by key
     */
    public function getDataValue(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Set data value by key
     */
    public function setDataValue(string $key, $value): void
    {
        $data = $this->data ?? [];
        $data[$key] = $value;
        $this->update(['data' => $data]);
    }

    /**
     * Scope for successful items
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for failed items
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for items by reference type
     */
    public function scopeByReferenceType($query, string $type)
    {
        return $query->where('reference_type', $type);
    }

    /**
     * Scope for items by customer
     */
    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }
}