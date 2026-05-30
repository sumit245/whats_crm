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
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;

class PayPalController extends Controller
{
    /**
     * Create a PayPal order and redirect the buyer to the approval page.
     */
    public function pay(Plans $plan, Order $order)
    {
        $client = $this->client();

        $currency = PaymentController::currencyCode($plan->symbol);

        $request = new OrdersCreateRequest();
        $request->prefer('return=representation');
        $request->body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $order->order_id,
                'description' => (string) $plan->title,
                'amount' => [
                    'currency_code' => $currency,
                    'value' => number_format((float) $order->amount, 2, '.', ''),
                ],
            ]],
            'application_context' => [
                'return_url' => route('payments.callback') . '?gateway=paypal&order_id=' . $order->order_id,
                'cancel_url' => url('/'),
                'brand_name' => config('config.site_name'),
                'user_action' => 'PAY_NOW',
            ],
        ];

        $response = $client->execute($request);

        foreach ($response->result->links as $link) {
            if ($link->rel === 'approve') {
                return redirect()->away($link->href);
            }
        }

        return redirect('/')->with('alert', [
            'type' => 'danger',
            'msg' => __('Unable to start the PayPal payment.'),
        ]);
    }

    /**
     * Capture the PayPal order when the buyer returns from approval.
     */
    public function callback(Request $request)
    {
        $orderId = $request->input('order_id', $request->query('order_id'));
        $paypalToken = $request->query('token');

        $order = Order::where('order_id', $orderId)->first();

        if (! $order || ! $paypalToken) {
            return redirect('/')->with('alert', [
                'type' => 'danger',
                'msg' => __('Unable to verify the payment.'),
            ]);
        }

        $client = $this->client();
        $capture = new OrdersCaptureRequest($paypalToken);
        $capture->prefer('return=representation');

        $response = $client->execute($capture);

        if (isset($response->result->status) && $response->result->status === 'COMPLETED') {
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

    /**
     * Build a PayPal HTTP client for the configured environment.
     */
    protected function client(): PayPalHttpClient
    {
        $clientId = config('payments.paypal.client_id');
        $clientSecret = config('payments.paypal.client_secret');

        $environment = config('payments.paypal.mode') === 'live'
            ? new ProductionEnvironment($clientId, $clientSecret)
            : new SandboxEnvironment($clientId, $clientSecret);

        return new PayPalHttpClient($environment);
    }
}
