<?php
/*
Copyright © Magd Almuntaser, OneXGen Technology. All rights reserved.
Project: MPWA Whatsapp Gateway | Multi Device
Licensed under the CC BY-NC-ND 4.0 License.
For details, visit https://creativecommons.org/licenses/by-nc-nd/4.0/.
*/

namespace App\Http\Controllers;

use App\Models\Plans;

class IndexController extends Controller
{
    /**
     * Render the public welcome / landing page with the active plans.
     */
    public function index()
    {
        $plans = Plans::where('status', 1)->orderBy('price')->get();

        return view('index::home', compact('plans'));
    }
}
