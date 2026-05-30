<?php

namespace App\Http\Controllers;

use App\Models\Segment;
use App\Services\SegmentEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SegmentController extends Controller
{
    public function index(Request $request)
    {
        $segments = Segment::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return view('theme::pages.segments.index', compact('segments'));
    }

    public function create()
    {
        return view('theme::pages.segments.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:100',
            'rules' => 'required|array',
        ]);

        try {
            $segment = Segment::create([
                'user_id' => $request->user()->id,
                'name'    => $request->name,
                'rules'   => $request->rules,
            ]);

            $segment->computeCount();

            return response()->json([
                'error'   => false,
                'message' => __('Segment created.'),
                'redirect' => route('segments.index'),
            ]);
        } catch (\Throwable $e) {
            Log::error($e);
            return response()->json(['error' => true, 'message' => $e->getMessage()]);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            Segment::where('user_id', $request->user()->id)->findOrFail($id)->delete();
            return response()->json(['error' => false, 'message' => __('Segment deleted.')]);
        } catch (\Throwable $e) {
            return response()->json(['error' => true, 'message' => __('Something went wrong.')]);
        }
    }

    /**
     * AJAX preview: return contact count without saving the segment.
     */
    public function preview(Request $request)
    {
        $request->validate(['rules' => 'required|array']);

        try {
            $segment = new Segment([
                'user_id' => $request->user()->id,
                'rules'   => $request->rules,
            ]);

            $count = (new SegmentEngine())->resolve($segment)->count();

            return response()->json(['error' => false, 'count' => $count]);
        } catch (\Throwable $e) {
            return response()->json(['error' => true, 'message' => $e->getMessage()]);
        }
    }
}
