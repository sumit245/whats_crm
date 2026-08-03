<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WaLinkController extends Controller
{
    public function index(): View
    {
        $device = auth()->user()->devices()->first();
        $phone  = $device?->body ?? '';

        return view('theme::pages.wa-link.index', compact('phone'));
    }
}
