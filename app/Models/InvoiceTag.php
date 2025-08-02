<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceTag extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'invoice_id',
        'tag',
    ];

    /**
     * Get the invoice that owns the tag.
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
