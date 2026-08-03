<?php

namespace App\Http\Controllers\Ads;

use App\Http\Controllers\Controller;
use App\Models\AdCreative;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdCreativesController extends Controller
{
    public function index(Request $request)
    {
        $creatives = AdCreative::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('theme::pages.ads.creatives.index', compact('creatives'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'   => 'required|string|max:150',
            'format' => 'required|in:text,image,video,carousel,story,reel',
            'body'   => 'nullable|string|max:2000',
        ]);

        $mediaPaths = [];
        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $file) {
                $mediaPaths[] = $file->store('ads/media', 'public');
            }
        }

        $carouselCards = [];
        if ($request->filled('carousel_cards')) {
            $carouselCards = json_decode($request->carousel_cards, true) ?? [];
        }

        $creative = AdCreative::create([
            'user_id'        => $request->user()->id,
            'ad_campaign_id' => $request->ad_campaign_id ?: null,
            'name'           => $request->name,
            'format'         => $request->format,
            'headline'       => $request->headline,
            'body'           => $request->body,
            'cta_text'       => $request->cta_text,
            'cta_url'        => $request->cta_url,
            'media_paths'    => $mediaPaths ?: null,
            'carousel_cards' => $carouselCards ?: null,
            'status'         => 'ready',
        ]);

        if ($request->ajax()) {
            return response()->json(['error' => false, 'id' => $creative->id, 'name' => $creative->name]);
        }

        return redirect()->route('ads.creatives.index')
            ->with('alert', ['type' => 'success', 'msg' => __('Creative saved.')]);
    }

    public function show(AdCreative $creative)
    {
        abort_unless($creative->user_id === request()->user()->id, 403);
        return view('theme::pages.ads.creatives.show', compact('creative'));
    }

    public function update(Request $request, AdCreative $creative)
    {
        abort_unless($creative->user_id === $request->user()->id, 403);

        $creative->update($request->only(['name', 'headline', 'body', 'cta_text', 'cta_url', 'status']));

        return redirect()->route('ads.creatives.index')
            ->with('alert', ['type' => 'success', 'msg' => __('Creative updated.')]);
    }

    public function destroy(AdCreative $creative)
    {
        abort_unless($creative->user_id === request()->user()->id, 403);
        $creative->delete();

        return redirect()->route('ads.creatives.index')
            ->with('alert', ['type' => 'success', 'msg' => __('Creative deleted.')]);
    }

    public function preview(Request $request, AdCreative $creative)
    {
        abort_unless($creative->user_id === request()->user()->id, 403);
        return response()->json([
            'format'         => $creative->format,
            'headline'       => $creative->headline,
            'body'           => $creative->body,
            'cta_text'       => $creative->cta_text,
            'cta_url'        => $creative->cta_url,
            'media_paths'    => $creative->media_paths,
            'carousel_cards' => $creative->carousel_cards,
        ]);
    }
}
