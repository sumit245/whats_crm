<?php

namespace App\Http\Controllers;

use App\Models\QuickReply;
use Illuminate\Http\Request;

class QuickReplyController extends Controller
{
    public function index(Request $request)
    {
        $replies = QuickReply::where('user_id', $request->user()->id)
            ->orderBy('shortcut')
            ->get();

        return view('theme::pages.quick-replies.index', compact('replies'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'shortcut' => 'required|string|max:30|regex:/^\S+$/',
            'title'    => 'required|string|max:60',
            'body'     => 'required|string|max:4096',
        ]);

        QuickReply::updateOrCreate(
            ['user_id' => $request->user()->id, 'shortcut' => $data['shortcut']],
            ['title' => $data['title'], 'body' => $data['body']]
        );

        return back()->with('success', __('Quick reply saved.'));
    }

    public function destroy(Request $request, QuickReply $quickReply)
    {
        abort_unless($quickReply->user_id === $request->user()->id, 403);
        $quickReply->delete();

        return back()->with('success', __('Quick reply deleted.'));
    }
}
