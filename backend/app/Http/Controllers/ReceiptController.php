<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;

class ReceiptController extends Controller
{
    public function show(Payment $payment)
    {
        Gate::authorize('manage-payments');

        $data = $this->receiptData($payment);
        $this->authorizePayment($payment);

        return view('receipts.payment', $data);
    }

    public function pdf(Payment $payment)
    {
        Gate::authorize('manage-payments');

        $data = $this->receiptData($payment) + ['forPdf' => true];
        $this->authorizePayment($payment);

        $pdf = Pdf::loadView('receipts.payment', $data)
            ->setPaper('a5');

        return $pdf->download('receipt-'.$this->receiptNumber($payment).'.pdf');
    }

    private function authorizePayment(Payment $payment): void
    {
        abort_unless(
            $payment->membership->member->gym_id === auth()->user()->gym_id,
            403
        );
    }

    private function receiptNumber(Payment $payment): string
    {
        return 'R-'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT);
    }

    private function receiptData(Payment $payment): array
    {
        $payment->loadMissing(['membership.member' => fn ($q) => $q->withTrashed(), 'membership.plan']);

        return [
            'payment' => $payment,
            'membership' => $payment->membership,
            'member' => $payment->membership->member,
            'plan' => $payment->membership->plan,
            'gym' => auth()->user()->gym,
            'receiptNumber' => $this->receiptNumber($payment),
            'forPdf' => false,
        ];
    }
}
