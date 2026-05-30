<?php
/*
Copyright © Magd Almuntaser, OneXGen Technology. All rights reserved.
Project: MPWA Whatsapp Gateway | Multi Device
Licensed under the CC BY-NC-ND 4.0 License.
For details, visit https://creativecommons.org/licenses/by-nc-nd/4.0/.
*/

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Http\Controllers\PaymentController;
use App\Models\Order;
use App\Models\Plans;
use App\Services\MidtransService;
use Illuminate\Http\Request;

class MidtransController extends Controller
{
    protected MidtransService $midtrans;

    public function __construct(MidtransService $midtrans)
    {
        $this->midtrans = $midtrans;
    }

    /**
     * Generate a Snap token and render the Midtrans payment page.
     */
    public function pay(Plans $plan, Order $order)
    {
        $snapToken = $this->midtrans->createSnapToken($order, $plan);

        return view('payments.midtrans', [
            'plan' => $plan,
            'snapToken' => $snapToken,
        ]);
    }

    /**
     * Handle the Snap.js success callback (posted from the payment page).
     */
    public function callback(Request $request)
    {
        $orderId = $request->input('order_id');
        $order = Order::where('order_id', $orderId)->first();

        if (! $order) {
            return response()->json(['success' => false, 'msg' => __('Order not found.')], 404);
        }

        // NOTE: Re-verify the transaction status against Midtrans rather than
        // trusting the browser payload before granting the subscription.
        try {
            $status = $this->midtrans->getStatus($orderId);
            $transactionStatus = $status->transaction_status ?? null;
            $fraudStatus = $status->fraud_status ?? null;
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'msg' => __('Unable to verify the payment.')], 422);
        }

        if ($transactionStatus && $this->midtrans->isPaid($transactionStatus, $fraudStatus)) {
            app(PaymentController::class)->fulfillOrder($order);

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'msg' => __('Payment was not completed.')], 422);
    }
}
