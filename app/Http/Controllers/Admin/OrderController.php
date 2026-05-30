<?php
/*
Copyright © Magd Almuntaser, OneXGen Technology. All rights reserved.
Project: MPWA Whatsapp Gateway | Multi Device
Licensed under the CC BY-NC-ND 4.0 License.
For details, visit https://creativecommons.org/licenses/by-nc-nd/4.0/.
*/

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;

class OrderController extends Controller
{
    /**
     * List all subscription/plan purchase orders.
     */
    public function index()
    {
        $orders = Order::with(['user', 'plan'])->latest()->paginate(15);

        return view('theme::pages.admin.orders.index', compact('orders'));
    }
}
