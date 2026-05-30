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
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class StripeController extends Controller
{
    /**
     * Create a Stripe Checkout session and render the redirect page.
     */
    public function pay(Plans $plan, Order $order)
    {
        Stripe::setApiKey(config('payments.stripe.secret_key'));

        $currency = strtolower(PaymentController::currencyCode($plan->symbol));

        // NOTE: Stripe expects the amount in the smallest currency unit. We
        // assume a 2-decimal currency here (the most common case).
        $unitAmount = (int) round($order->amount * 100);

        $session = Session::create([
            'mode' => 'payment',
            'line_items' => [[
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => [
                        'name' => (string) $plan->title,
                    ],
                    'unit_amount' => $unitAmount,
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'order_id' => $order->order_id,
            ],
            'success_url' => route('payments.callback') . '?gateway=stripe&order_id=' . $order->order_id . '&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => url('/'),
        ]);

        return view('payments.stripe', [
            'plan' => $plan,
            'publicKey' => config('payments.stripe.publishable_key'),
            'checkoutSessionId' => $session->id,
        ]);
    }

    /**
     * Handle the redirect back from Stripe Checkout.
     */
    public function callback(Request $request)
    {
        Stripe::setApiKey(config('payments.stripe.secret_key'));

        $sessionId = $request->query('session_id');
        $orderId = $request->input('order_id', $request->query('order_id'));

        $order = Order::where('order_id', $orderId)->first();

        if (! $order || ! $sessionId) {
            return redirect('/')->with('alert', [
                'type' => 'danger',
                'msg' => __('Unable to verify the payment.'),
            ]);
        }

        $session = Session::retrieve($sessionId);

        if ($session && $session->payment_status === 'paid') {
            app(PaymentController::class)->fulfillOrder($order);

            return redirect('home')->with('alert', [
                'type' => 'success',
                'msg' => __('The plan has been paid.'),
            ]);
        }

        return redirect('/')->with('alert', [
            'type' => 'danger',
            'msg' => __('Payment was not completed.'),
        ]);
    }
}
