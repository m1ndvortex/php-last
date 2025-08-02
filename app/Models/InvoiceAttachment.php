<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceAttachment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'invoice_id',
        'filename',
        'original_filename',
        'file_path',
        'mime_type',
        'file_size',
    ];

    /**
     * Get the invoice that owns the attachment.
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
