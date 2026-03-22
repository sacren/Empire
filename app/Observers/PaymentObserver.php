<?php

namespace App\Observers;

use App\Mail\PaymentReceived;
use App\Models\Payment;
use Illuminate\Support\Facades\Mail;

class PaymentObserver
{
    /**
     * Handle the Payment "created" event.
     */
    public function created(Payment $payment): void
    {
        $payment->enrollment->recalculateStatus();

        Mail::to($payment->enrollment->prospect->email)->send(new PaymentReceived($payment));
    }
}
