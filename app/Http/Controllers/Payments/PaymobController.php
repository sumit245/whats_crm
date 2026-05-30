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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymobController extends Controller
{
    /**
     * Route-facing entry point (POST /payments/paymob/process).
     *
     * Resolves the plan from the request, creates a pending order and starts
     * the Paymob checkout.
     */
    public function process(Request $request)
    {
        $request->validate([
            'planId' => 'required',
        ]);

        $plan = Plans::where('status', 1)->findOrFail($request->input('planId'));
        $order = app(PaymentController::class)->createOrder($plan, 'paymob');

        return $this->pay($plan, $order);
    }

    /**
     * Create a Paymob payment intention and redirect to the unified checkout.
     */
    public function pay(Plans $plan, Order $order)
    {
        $secretKey = config('payments.paymob.secret_key');
        $publicKey = config('payments.paymob.public_key');
        $integrationId = config('payments.paymob.integration_id');

        $user = $order->user ?? Auth::user();
        $amountCents = (int) round($order->amount * 100);

        $payload = [
            'amount' => $amountCents,
            'currency' => 'EGP',
            'payment_methods' => [(int) $integrationId],
            'special_reference' => $order->order_id,
            'items' => [[
                'name' => (string) $plan->title,
                'amount' => $amountCents,
                'quantity' => 1,
            ]],
            'billing_data' => [
                'first_name' => $user->username ?? 'Customer',
                'last_name' => $user->username ?? 'Customer',
                'email' => $user->email ?? 'customer@example.com',
                'phone_number' => '+00000000000',
            ],
            'redirection_url' => route('payments.paymob.callback') . '?order_id=' . $order->order_id,
        ];

        $response = Http::withToken($secretKey)
            ->acceptJson()
            ->post('https://accept.paymob.com/v1/intention/', $payload);

        if (! $response->successful() || ! ($clientSecret = $response->json('client_secret'))) {
            Log::error('Paymob intention failed: ' . $response->body());

            return redirect('/')->with('alert', [
                'type' => 'danger',
                'msg' => __('Unable to start the Paymob payment.'),
            ]);
        }

        $checkoutUrl = 'https://accept.paymob.com/unifiedcheckout/?publicKey='
            . urlencode($publicKey) . '&clientSecret=' . urlencode($clientSecret);

        return redirect()->away($checkoutUrl);
    }

    /**
     * Handle the Paymob transaction processed callback / redirect.
     */
    public function callback(Request $request)
    {
        $data = $request->all();

        $orderId = $request->input('order_id', $request->query('order_id'));
        $orderId = $orderId ?: ($data['merchant_order_id'] ?? ($data['special_reference'] ?? null));

        $order = $orderId ? Order::where('order_id', $orderId)->first() : null;

        $success = filter_var($data['success'] ?? $request->input('success'), FILTER_VALIDATE_BOOLEAN);

        // NOTE: When Paymob includes an HMAC we verify it; otherwise we fall back
        // to the success flag from the redirect.
        if ($request->filled('hmac') && ! $this->verifyHmac($request)) {
            Log::warning('Paymob HMAC verification failed for order ' . $orderId);
            $success = false;
        }

        if ($order && $success) {
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
     * Verify the Paymob HMAC signature for a processed-transaction callback.
     */
    protected function verifyHmac(Request $request): bool
    {
        $hmacKey = config('payments.paymob.hmac_key');
        $received = $request->input('hmac');

        // Paymob concatenates these fields (in this fixed order) before hashing.
        $fields = [
            'amount_cents', 'created_at', 'currency', 'error_occured', 'has_parent_transaction',
            'id', 'integration_id', 'is_3d_secure', 'is_auth', 'is_capture', 'is_refunded',
            'is_standalone_payment', 'is_voided', 'order', 'owner', 'pending',
            'source_data_pan', 'source_data_sub_type', 'source_data_type', 'success',
        ];

        $concatenated = '';
        foreach ($fields as $field) {
            $value = data_get($request->all(), $field);
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            $concatenated .= $value;
        }

        $calculated = hash_hmac('sha512', $concatenated, (string) $hmacKey);

        return hash_equals($calculated, (string) $received);
    }
}
