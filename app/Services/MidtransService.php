<?php
/*
Copyright © Magd Almuntaser, OneXGen Technology. All rights reserved.
Project: MPWA Whatsapp Gateway | Multi Device
Licensed under the CC BY-NC-ND 4.0 License.
For details, visit https://creativecommons.org/licenses/by-nc-nd/4.0/.
*/

namespace App\Services;

use App\Models\Order;
use App\Models\Plans;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = config('payments.midtrans.server_key');
        Config::$isProduction = filter_var(config('payments.midtrans.is_production'), FILTER_VALIDATE_BOOLEAN);
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    /**
     * Create a Snap token for the given order/plan so the front-end Snap.js
     * widget can render the payment popup.
     */
    public function createSnapToken(Order $order, Plans $plan): string
    {
        $payload = [
            'transaction_details' => [
                'order_id' => $order->order_id,
                'gross_amount' => (int) round($order->amount),
            ],
            'item_details' => [
                [
                    'id' => $plan->id,
                    'price' => (int) round($order->amount),
                    'quantity' => 1,
                    'name' => mb_substr((string) $plan->title, 0, 50),
                ],
            ],
            'customer_details' => [
                'first_name' => $order->user->username ?? 'Customer',
                'email' => $order->user->email ?? null,
            ],
        ];

        return Snap::getSnapToken($payload);
    }

    /**
     * Fetch the current status of a transaction from Midtrans.
     *
     * @return object
     */
    public function getStatus(string $orderId)
    {
        return Transaction::status($orderId);
    }

    /**
     * Determine whether a Midtrans transaction status represents a settled
     * (successfully paid) payment.
     */
    public function isPaid(string $transactionStatus, ?string $fraudStatus = null): bool
    {
        if (in_array($transactionStatus, ['capture'], true)) {
            return $fraudStatus === null || $fraudStatus === 'accept';
        }

        return $transactionStatus === 'settlement';
    }
}
