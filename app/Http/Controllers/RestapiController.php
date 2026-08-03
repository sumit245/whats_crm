<?php
/*
Copyright © Magd Almuntaser, OneXGen Technology. All rights reserved.
Project: MPWA Whatsapp Gateway | Multi Device
Licensed under the CC BY-NC-ND 4.0 License.
For details, visit https://creativecommons.org/licenses/by-nc-nd/4.0/.
*/

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RestapiController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $user    = auth()->user();
        $device  = $user->devices()->first();
        $apiKey  = $user->api_key;
        $sender  = $device?->body ?? 'YOUR_DEVICE_NAME';
        $baseUrl = rtrim(url('/'), '/') . '/' . app()->getLocale();

        return view('theme::pages.api-docs.index', compact('apiKey', 'sender', 'baseUrl', 'device'));
    }
}
