<?php

namespace App\Http\Controllers;

use App\Models\LinkClick;
use App\Models\TrackedLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LinkClickController extends Controller
{
    public function redirect(Request $request, string $slug)
    {
        $link = TrackedLink::where('slug', $slug)->first();

        if (!$link) {
            abort(404);
        }

        try {
            LinkClick::create([
                'tracked_link_id' => $link->id,
                'contact_number'  => $request->query('c') ? substr($request->query('c'), 0, 30) : null,
                'ip_address'      => $request->ip(),
                'user_agent'      => substr($request->userAgent() ?? '', 0, 512),
                'clicked_at'      => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('LinkClickController: failed to record click', ['slug' => $slug, 'error' => $e->getMessage()]);
        }

        return redirect()->away($link->original_url);
    }
}
