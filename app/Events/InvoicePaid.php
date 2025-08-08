<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoicePaid
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Invoice $invoice;
    public array $paymentData;

    /**
     * Create a new event instance.
     */
    public function __construct(Invoice $invoice, array $paymentData = [])
    {
        $this->invoice = $invoice;
        $this->paymentData = $paymentData;
    }
}