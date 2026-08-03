<?php

namespace App\Http\Controllers;

use App\Models\OptInSetting;
use App\Models\Tag;
use Illuminate\Http\Request;

class OptInSettingController extends Controller
{
    public function show(Request $request)
    {
        $user      = $request->user();
        $settings  = OptInSetting::forUser($user->id);
        $phonebooks = Tag::where('user_id', $user->id)->orderBy('name')->get();

        return view('theme::pages.opt-in.index', compact('settings', 'phonebooks'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'opt_out_keywords'    => 'required|string',
            'opt_in_keyword'      => 'nullable|string|max:64',
            'opt_in_phonebook_id' => 'nullable|exists:tags,id',
            'opt_in_reply'        => 'nullable|string|max:1000',
            'opt_out_reply'       => 'nullable|string|max:1000',
        ]);

        $user     = $request->user();
        $settings = OptInSetting::forUser($user->id);

        // Parse comma-separated keywords into array, upper-cased and trimmed
        $keywords = collect(explode(',', $request->opt_out_keywords))
            ->map(fn ($k) => strtoupper(trim($k)))
            ->filter()
            ->values()
            ->all();

        $settings->update([
            'opt_out_keywords'    => $keywords,
            'opt_in_keyword'      => $request->opt_in_keyword ? strtoupper(trim($request->opt_in_keyword)) : null,
            'opt_in_phonebook_id' => $request->opt_in_phonebook_id ?: null,
            'opt_in_reply'        => $request->opt_in_reply ?: null,
            'opt_out_reply'       => $request->opt_out_reply ?: null,
            'opt_in_enabled'      => $request->boolean('opt_in_enabled'),
            'opt_out_enabled'     => $request->boolean('opt_out_enabled'),
        ]);

        return back()->with('alert', ['type' => 'success', 'msg' => __('Opt-in / Opt-out settings saved.')]);
    }
}
