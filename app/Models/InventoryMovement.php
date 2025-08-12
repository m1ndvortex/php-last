<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_item_id',
        'from_location_id',
        'to_location_id',
        'type',
        'quantity',
        'unit_cost',
        'reference_type',
        'reference_id',
        'batch_number',
        'notes',
        'user_id',
        'created_by',
        'movement_date',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'movement_date' => 'datetime',
    ];

    const TYPE_IN = 'in';
    const TYPE_OUT = 'out';
    const TYPE_TRANSFER = 'transfer';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_WASTAGE = 'wastage';
    const TYPE_PRODUCTION = 'production';
    const TYPE_SALE = 'sale';
    const TYPE_RETURN = 'return';

    /**
     * Get the inventory item that owns the movement.
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Get the from location.
     */
    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    /**
     * Get the to location.
     */
    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    /**
     * Get the user who created the movement.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who created the movement (alias for created_by).
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the accounting entries related to this movement.
     */
    public function accountingEntries()
    {
        return $this->morphMany(Transaction::class, 'source');
    }

    /**
     * Get the total value of this movement.
     */
    public function getTotalValueAttribute(): float
    {
        return (float) ($this->quantity * ($this->unit_cost ?? 0));
    }

    /**
     * Get the quantity change (positive for inbound, negative for outbound).
     */
    public function getQuantityChangeAttribute(): float
    {
        if ($this->is_inbound) {
            return (float) $this->quantity;
        } elseif ($this->is_outbound) {
            return -(float) $this->quantity;
        }
        return 0;
    }

    /**
     * Check if this is an inbound movement.
     */
    public function getIsInboundAttribute(): bool
    {
        return in_array($this->type, [self::TYPE_IN, self::TYPE_PRODUCTION]);
    }

    /**
     * Check if this is an outbound movement.
     */
    public function getIsOutboundAttribute(): bool
    {
        return in_array($this->type, [self::TYPE_OUT, self::TYPE_WASTAGE, self::TYPE_SALE]);
    }

    /**
     * Check if this is a transfer movement.
     */
    public function getIsTransferAttribute(): bool
    {
        return $this->type === self::TYPE_TRANSFER;
    }

    /**
     * Scope to filter by movement type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('movement_date', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by reference.
     */
    public function scopeForReference($query, string $referenceType, int $referenceId)
    {
        return $query->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId);
    }
}
