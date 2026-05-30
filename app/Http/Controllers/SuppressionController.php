<?php

namespace App\Http\Controllers;

use App\Models\SuppressionEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SuppressionController extends Controller
{
    public function index(Request $request)
    {
        $entries = SuppressionEntry::where('user_id', $request->user()->id)
            ->when($request->search, fn ($q) => $q->where('number', 'like', '%' . $request->search . '%'))
            ->latest()
            ->paginate(20);

        return view('theme::pages.suppression.index', compact('entries'));
    }

    public function store(Request $request)
    {
        $request->validate(['number' => 'required|string|max:20']);

        try {
            SuppressionEntry::suppress(
                $request->user()->id,
                $request->number,
                'manual',
                $request->note
            );
            return response()->json(['error' => false, 'message' => __('Number suppressed.')]);
        } catch (\Throwable $e) {
            Log::error($e);
            return response()->json(['error' => true, 'message' => __('Something went wrong.')]);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            SuppressionEntry::where('user_id', $request->user()->id)->findOrFail($id)->delete();
            return response()->json(['error' => false, 'message' => __('Number removed from suppression list.')]);
        } catch (\Throwable $e) {
            return response()->json(['error' => true, 'message' => __('Something went wrong.')]);
        }
    }
}
