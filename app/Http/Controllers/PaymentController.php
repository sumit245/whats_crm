<?php
/*
Copyright © Magd Almuntaser, OneXGen Technology. All rights reserved.
Project: MPWA Whatsapp Gateway | Multi Device
Licensed under the CC BY-NC-ND 4.0 License.
For details, visit https://creativecommons.org/licenses/by-nc-nd/4.0/.
*/

namespace App\Http\Controllers;

use App\Http\Controllers\Payments\MidtransController;
use App\Http\Controllers\Payments\PaymobController;
use App\Http\Controllers\Payments\PayPalController;
use App\Http\Controllers\Payments\StripeController;
use App\Models\Order;
use App\Models\Plans;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Show the checkout page for a paid plan and let the user pick a gateway.
     */
    public function checkout($planId)
    {
        $plan = Plans::where('status', 1)->findOrFail($planId);

        $gateways = $this->enabledGateways();

        if (empty($gateways)) {
            return redirect('/')->with('alert', [
                'type' => 'danger',
                'msg' => __('No payment gateway is currently available.'),
            ]);
        }

        return view('index::checkout', compact('plan', 'gateways'));
    }

    /**
     * Create a pending order and hand off to the selected payment gateway.
     */
    public function process(Request $request, $planId)
    {
        $request->validate([
            'payment_gateway' => 'required|string|in:midtrans,stripe,paypal,paymob',
        ]);

        $plan = Plans::where('status', 1)->findOrFail($planId);
        $gateway = $request->input('payment_gateway');

        if (! array_key_exists($gateway, $this->enabledGateways())) {
            return back()->withErrors(['payment_gateway' => __('This payment gateway is not available.')]);
        }

        $order = $this->createOrder($plan, $gateway);

        try {
            switch ($gateway) {
                case 'midtrans':
                    return app(MidtransController::class)->pay($plan, $order);
                case 'stripe':
                    return app(StripeController::class)->pay($plan, $order);
                case 'paypal':
                    return app(PayPalController::class)->pay($plan, $order);
                case 'paymob':
                    return app(PaymobController::class)->pay($plan, $order);
            }
        } catch (\Throwable $e) {
            Log::error('Payment initiation failed: ' . $e->getMessage());

            return back()->withErrors([
                'payment_gateway' => __('Unable to start the payment. Please try again later.'),
            ]);
        }

        return back()->withErrors(['payment_gateway' => __('Unsupported payment gateway.')]);
    }

    /**
     * Show the trial activation page.
     */
    public function trial($planId)
    {
        $plan = Plans::where('status', 1)->where('is_trial', 1)->findOrFail($planId);

        if ((int) Auth::user()->trial_plan === 1) {
            return redirect('/')->with('alert', [
                'type' => 'danger',
                'msg' => __('You have already used your free trial.'),
            ]);
        }

        return view('index::trial', compact('plan'));
    }

    /**
     * Activate a free trial subscription for the authenticated user.
     */
    public function trialProcess(Request $request, $planId)
    {
        $plan = Plans::where('status', 1)->where('is_trial', 1)->findOrFail($planId);
        $user = Auth::user();

        if ((int) $user->trial_plan === 1) {
            return redirect('/')->with('alert', [
                'type' => 'danger',
                'msg' => __('You have already used your free trial.'),
            ]);
        }

        // A trial uses the dedicated env limits rather than the paid plan limits.
        $planData = is_array($plan->data) ? $plan->data : [];
        $planData['messages_limit'] = (int) env('TRIAL_MESSAGE_LIMIT', $planData['messages_limit'] ?? 0);
        $planData['device_limit'] = (int) env('TRIAL_DEVICES_LIMIT', $planData['device_limit'] ?? 1);

        $order = $this->createOrder($plan, 'trial');

        $user->trial_plan = 1;
        $user->active_subscription = 'active';
        $user->subscription_expired = Carbon::now()->addDays((int) $plan->trial_days);
        $user->limit_device = $planData['device_limit'];
        $user->plan_name = $plan->title;
        $user->plan_data = $planData;
        $user->save();

        $order->status = 'completed';
        $order->save();

        return redirect('home')->with('alert', [
            'type' => 'success',
            'msg' => __('Your free trial has been activated.'),
        ]);
    }

    /**
     * Generic browser/return callback used by Stripe (GET) and Midtrans (POST).
     */
    public function callback(Request $request)
    {
        $gateway = $request->input('gateway', $request->query('gateway'));

        try {
            switch ($gateway) {
                case 'midtrans':
                    return app(MidtransController::class)->callback($request);
                case 'stripe':
                    return app(StripeController::class)->callback($request);
                case 'paypal':
                    return app(PayPalController::class)->callback($request);
            }
        } catch (\Throwable $e) {
            Log::error('Payment callback failed: ' . $e->getMessage());
        }

        return redirect('/')->with('alert', [
            'type' => 'danger',
            'msg' => __('Unable to verify the payment.'),
        ]);
    }

    /**
     * Create a pending order for the given plan and gateway.
     */
    public function createOrder(Plans $plan, string $gateway): Order
    {
        return Order::create([
            'user_id' => Auth::id(),
            'plan_id' => $plan->id,
            'order_id' => 'ORD-' . strtoupper(Str::random(8)) . '-' . time(),
            'amount' => $plan->price,
            'status' => 'pending',
            'payment_gateway' => $gateway,
        ]);
    }

    /**
     * Mark an order as paid and grant the matching subscription to the user.
     */
    public function fulfillOrder(Order $order): void
    {
        if ($order->status === 'completed') {
            return;
        }

        $plan = $order->plan;
        $user = $order->user;

        if (! $plan || ! $user) {
            return;
        }

        $planData = is_array($plan->data) ? $plan->data : [];

        $user->active_subscription = 'active';
        $user->subscription_expired = Carbon::now()->addDays((int) $plan->days);
        $user->limit_device = $planData['device_limit'] ?? $user->limit_device;
        $user->plan_name = $plan->title;
        $user->plan_data = $planData;
        $user->save();

        $order->status = 'completed';
        $order->save();
    }

    /**
     * The set of enabled gateways keyed by name.
     *
     * @return array<string, array>
     */
    protected function enabledGateways(): array
    {
        $gateways = [];

        foreach ((array) config('payments', []) as $name => $config) {
            if (($config['status'] ?? 'disable') === 'enable') {
                $gateways[$name] = $config;
            }
        }

        return $gateways;
    }

    /**
     * Resolve a 3-letter ISO currency code from the plan currency symbol.
     */
    public static function currencyCode($symbol): string
    {
        $symbol = (string) $symbol;

        if (preg_match('/^[A-Za-z]{3}$/', $symbol)) {
            return strtoupper($symbol);
        }

        $map = [
            '$' => 'USD',
            '?' => 'EUR',
            '£' => 'GBP',
            '¥' => 'JPY',
            '?' => 'INR',
            '?' => 'RUB',
        ];

        return $map[$symbol] ?? 'USD';
    }
}
