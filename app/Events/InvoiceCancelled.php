<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceCancelled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Invoice $invoice;
    public string $reason;

    /**
     * Create a new event instance.
     */
    public function __construct(Invoice $invoice, string $reason = '')
    {
        $this->invoice = $invoice;
        $this->reason = $reason;
    }
}