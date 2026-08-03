<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Device;
use App\Models\DripEnrollment;
use App\Models\DripSequence;
use App\Models\DripStep;
use App\Models\Tag;
use App\Models\WabaTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DripSequenceController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $sequences = DripSequence::where('user_id', $userId)
            ->withCount(['enrollments', 'steps',
                'enrollments as active_count'    => fn ($q) => $q->where('status', 'active'),
                'enrollments as completed_count'  => fn ($q) => $q->where('status', 'completed'),
            ])
            ->orderByDesc('created_at')
            ->get();

        return view('theme::pages.drip.index', compact('sequences'));
    }

    public function create(Request $request)
    {
        $userId    = $request->user()->id;
        $devices   = Device::where('user_id', $userId)->get();
        $phonebooks = Tag::where('user_id', $userId)->orderBy('name')->get();
        $templates = WabaTemplate::where('user_id', $userId)->where('status', 'APPROVED')->orderBy('name')->get();
        $sequence  = null;
        $steps     = collect();

        return view('theme::pages.drip.create', compact('sequence', 'steps', 'devices', 'phonebooks', 'templates'));
    }

    public function store(Request $request)
    {
        $userId = $request->user()->id;

        $validated = $request->validate([
            'name'                 => 'required|string|max:200',
            'description'          => 'nullable|string|max:1000',
            'status'               => 'required|in:draft,active,paused',
            'trigger_type'         => 'required|in:manual,opt_in,phonebook_add',
            'trigger_phonebook_id' => 'nullable|exists:tags,id',
            'default_device_id'    => 'nullable|exists:devices,id',
            'steps'                => 'array',
            'steps.*.delay_value'  => 'required|integer|min:0',
            'steps.*.delay_unit'   => 'required|in:minutes,hours,days',
            'steps.*.message_type' => 'required|in:text,template',
            'steps.*.message_body' => 'nullable|string',
            'steps.*.template_id'  => 'nullable|exists:waba_templates,id',
            'steps.*.skip_if_replied' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($userId, $validated) {
            $seq = DripSequence::create([
                'user_id'              => $userId,
                'name'                 => $validated['name'],
                'description'          => $validated['description'] ?? null,
                'status'               => $validated['status'],
                'trigger_type'         => $validated['trigger_type'],
                'trigger_phonebook_id' => $validated['trigger_phonebook_id'] ?? null,
                'default_device_id'    => $validated['default_device_id'] ?? null,
            ]);

            foreach ($validated['steps'] ?? [] as $i => $stepData) {
                DripStep::create([
                    'sequence_id'      => $seq->id,
                    'step_order'       => $i + 1,
                    'delay_value'      => $stepData['delay_value'],
                    'delay_unit'       => $stepData['delay_unit'],
                    'message_type'     => $stepData['message_type'],
                    'message_body'     => $stepData['message_body'] ?? null,
                    'template_id'      => $stepData['template_id'] ?? null,
                    'skip_if_replied'  => !empty($stepData['skip_if_replied']),
                ]);
            }
        });

        return redirect()->route('drip.index')->with('success', __('Sequence created.'));
    }

    public function edit(Request $request, int $id)
    {
        $userId   = $request->user()->id;
        $sequence = DripSequence::where('user_id', $userId)->with('steps')->findOrFail($id);
        $steps    = $sequence->steps;

        $devices   = Device::where('user_id', $userId)->get();
        $phonebooks = Tag::where('user_id', $userId)->orderBy('name')->get();
        $templates = WabaTemplate::where('user_id', $userId)->where('status', 'APPROVED')->orderBy('name')->get();

        return view('theme::pages.drip.create', compact('sequence', 'steps', 'devices', 'phonebooks', 'templates'));
    }

    public function update(Request $request, int $id)
    {
        $userId   = $request->user()->id;
        $sequence = DripSequence::where('user_id', $userId)->findOrFail($id);

        $validated = $request->validate([
            'name'                 => 'required|string|max:200',
            'description'          => 'nullable|string|max:1000',
            'status'               => 'required|in:draft,active,paused',
            'trigger_type'         => 'required|in:manual,opt_in,phonebook_add',
            'trigger_phonebook_id' => 'nullable|exists:tags,id',
            'default_device_id'    => 'nullable|exists:devices,id',
            'steps'                => 'array',
            'steps.*.delay_value'  => 'required|integer|min:0',
            'steps.*.delay_unit'   => 'required|in:minutes,hours,days',
            'steps.*.message_type' => 'required|in:text,template',
            'steps.*.message_body' => 'nullable|string',
            'steps.*.template_id'  => 'nullable|exists:waba_templates,id',
            'steps.*.skip_if_replied' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($sequence, $validated) {
            $sequence->update([
                'name'                 => $validated['name'],
                'description'          => $validated['description'] ?? null,
                'status'               => $validated['status'],
                'trigger_type'         => $validated['trigger_type'],
                'trigger_phonebook_id' => $validated['trigger_phonebook_id'] ?? null,
                'default_device_id'    => $validated['default_device_id'] ?? null,
            ]);

            // Replace all steps
            DripStep::where('sequence_id', $sequence->id)->delete();

            foreach ($validated['steps'] ?? [] as $i => $stepData) {
                DripStep::create([
                    'sequence_id'     => $sequence->id,
                    'step_order'      => $i + 1,
                    'delay_value'     => $stepData['delay_value'],
                    'delay_unit'      => $stepData['delay_unit'],
                    'message_type'    => $stepData['message_type'],
                    'message_body'    => $stepData['message_body'] ?? null,
                    'template_id'     => $stepData['template_id'] ?? null,
                    'skip_if_replied' => !empty($stepData['skip_if_replied']),
                ]);
            }
        });

        return redirect()->route('drip.index')->with('success', __('Sequence updated.'));
    }

    public function destroy(Request $request, int $id)
    {
        DripSequence::where('user_id', $request->user()->id)->findOrFail($id)->delete();
        return redirect()->route('drip.index')->with('success', __('Sequence deleted.'));
    }

    // ── Enrollments ──────────────────────────────────────────────────────────

    public function enrollments(Request $request, int $id)
    {
        $userId   = $request->user()->id;
        $sequence = DripSequence::where('user_id', $userId)->with('steps')->findOrFail($id);

        $enrollments = DripEnrollment::where('sequence_id', $id)
            ->orderByDesc('enrolled_at')
            ->paginate(30);

        $devices = Device::where('user_id', $userId)->get();

        return view('theme::pages.drip.enrollments', compact('sequence', 'enrollments', 'devices'));
    }

    public function enroll(Request $request, int $id)
    {
        $userId   = $request->user()->id;
        $sequence = DripSequence::where('user_id', $userId)->with('steps')->findOrFail($id);

        $request->validate([
            'contact_numbers' => 'required|string',
            'device_id'       => 'required|exists:devices,id',
        ]);

        $deviceId = $request->input('device_id');
        $numbers  = array_filter(array_map('trim', preg_split('/[\s,]+/', $request->input('contact_numbers'))));

        $enrolled = 0;
        foreach ($numbers as $number) {
            $result = $sequence->enroll($number, $userId, $deviceId);
            if ($result) $enrolled++;
        }

        return back()->with('success', __(':count contact(s) enrolled.', ['count' => $enrolled]));
    }

    public function cancelEnrollment(Request $request, int $enrollmentId)
    {
        $userId     = $request->user()->id;
        $enrollment = DripEnrollment::where('user_id', $userId)->findOrFail($enrollmentId);
        $enrollment->update(['status' => 'cancelled', 'next_run_at' => null]);

        return back()->with('success', __('Enrollment cancelled.'));
    }
}
