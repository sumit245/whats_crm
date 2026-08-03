<?php

namespace App\Http\Controllers\Ads;

use App\Http\Controllers\Controller;
use App\Models\AdAudience;
use App\Models\AdChannel;
use Illuminate\Http\Request;

class AdAudiencesController extends Controller
{
    public function index(Request $request)
    {
        $audiences = AdAudience::where('user_id', $request->user()->id)
            ->with('channel')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $channels = AdChannel::where('user_id', $request->user()->id)->where('status', 'active')->get();

        return view('theme::pages.ads.audiences', compact('audiences', 'channels'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:100',
            'ad_channel_id' => 'nullable|exists:ad_channels,id',
            'definition'    => 'required|array',
        ]);

        AdAudience::create([
            'user_id'       => $request->user()->id,
            'ad_channel_id' => $request->ad_channel_id ?: null,
            'name'          => $request->name,
            'definition'    => $request->definition,
        ]);

        return redirect()->route('ads.audiences.index')
            ->with('alert', ['type' => 'success', 'msg' => __('Audience saved.')]);
    }

    public function destroy(AdAudience $audience)
    {
        abort_unless($audience->user_id === request()->user()->id, 403);
        $audience->delete();

        return redirect()->route('ads.audiences.index')
            ->with('alert', ['type' => 'success', 'msg' => __('Audience deleted.')]);
    }

    public function sync(Request $request, AdAudience $audience)
    {
        abort_unless($audience->user_id === request()->user()->id, 403);

        // Sprint 5 will call the channel API to get estimated_size.
        return response()->json(['error' => false, 'msg' => __('Audience sync queued.')]);
    }
}
