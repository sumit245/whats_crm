<?php

namespace App\Http\Controllers;

use App\Jobs\DecideAbWinnerJob;
use App\Models\AbHoldoutContact;
use App\Models\AbTest;
use App\Models\AbVariant;
use App\Models\Blast;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Device;
use App\Models\Tag;
use App\Models\WabaTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AbTestController extends Controller
{
    public function index(Request $request)
    {
        $tests = AbTest::where('user_id', $request->user()->id)
            ->with(['variants', 'phonebook:id,name', 'winnerVariant'])
            ->withCount('holdoutContacts')
            ->orderByDesc('created_at')
            ->get();

        return view('theme::pages.ab.index', compact('tests'));
    }

    public function create(Request $request)
    {
        $userId    = $request->user()->id;
        $devices   = Device::where('user_id', $userId)->get();
        $phonebooks = Tag::where('user_id', $userId)->orderBy('name')->get();
        $templates = WabaTemplate::where('user_id', $userId)->where('status', 'APPROVED')->orderBy('name')->get(['id', 'name', 'language', 'components']);

        return view('theme::pages.ab.create', compact('devices', 'phonebooks', 'templates'));
    }

    public function store(Request $request)
    {
        $userId = $request->user()->id;

        $request->validate([
            'name'                => 'required|string|max:200',
            'phonebook_id'        => 'required|exists:tags,id',
            'device_id'           => 'required|exists:devices,id',
            'holdout_percent'     => 'required|integer|min:0|max:50',
            'winner_metric'       => 'required|in:read_rate,delivery_rate,click_rate',
            'decide_after_hours'  => 'required|integer|min:1|max:720',
            'variant_a_type'      => 'required|in:text,template',
            'variant_b_type'      => 'required|in:text,template',
            'variant_a_body'      => 'nullable|string',
            'variant_a_template'  => 'nullable|exists:waba_templates,id',
            'variant_b_body'      => 'nullable|string',
            'variant_b_template'  => 'nullable|exists:waba_templates,id',
        ]);

        $test = DB::transaction(function () use ($request, $userId) {
            $test = AbTest::create([
                'user_id'            => $userId,
                'name'               => $request->name,
                'phonebook_id'       => $request->phonebook_id,
                'device_id'          => $request->device_id,
                'holdout_percent'    => $request->holdout_percent,
                'winner_metric'      => $request->winner_metric,
                'decide_after_hours' => $request->decide_after_hours,
                'status'             => 'draft',
            ]);

            AbVariant::create([
                'ab_test_id'   => $test->id,
                'label'        => 'A',
                'message_type' => $request->variant_a_type,
                'message_body' => $request->variant_a_body,
                'template_id'  => $request->variant_a_template,
            ]);

            AbVariant::create([
                'ab_test_id'   => $test->id,
                'label'        => 'B',
                'message_type' => $request->variant_b_type,
                'message_body' => $request->variant_b_body,
                'template_id'  => $request->variant_b_template,
            ]);

            return $test;
        });

        return redirect()->route('ab.show', $test->id)->with('success', __('A/B test created. Launch it when ready.'));
    }

    public function show(Request $request, int $id)
    {
        $userId = $request->user()->id;
        $test   = AbTest::where('user_id', $userId)
            ->with(['variants.template', 'variants.campaign', 'phonebook:id,name', 'winnerVariant', 'device'])
            ->withCount('holdoutContacts')
            ->findOrFail($id);

        // Attach live metrics to each variant
        $variantMetrics = $test->variants->mapWithKeys(fn ($v) => [$v->id => $v->metrics()]);

        $contactCount = Contact::where('user_id', $userId)
            ->where('tag_id', $test->phonebook_id)
            ->count();

        return view('theme::pages.ab.show', compact('test', 'variantMetrics', 'contactCount'));
    }

    public function launch(Request $request, int $id)
    {
        $userId = $request->user()->id;
        $test   = AbTest::where('user_id', $userId)
            ->where('status', 'draft')
            ->with(['variants.template', 'device', 'phonebook'])
            ->findOrFail($id);

        $device = $test->device;
        if (!$device) {
            return back()->withErrors(['error' => __('Device not found.')]);
        }

        // Get and shuffle contacts from phonebook
        $contacts = Contact::where('user_id', $userId)
            ->where('tag_id', $test->phonebook_id)
            ->pluck('number')
            ->shuffle()
            ->values();

        $total        = $contacts->count();
        if ($total < 4) {
            return back()->withErrors(['error' => __('Need at least 4 contacts to run an A/B test.')]);
        }

        $holdoutCount = (int) round($total * $test->holdout_percent / 100);
        $variantPool  = $total - $holdoutCount;
        $variantACount = (int) floor($variantPool / 2);
        $variantBCount = $variantPool - $variantACount;

        $variantA  = $test->variants->firstWhere('label', 'A');
        $variantB  = $test->variants->firstWhere('label', 'B');
        $decideAt  = now()->addHours($test->decide_after_hours);

        DB::transaction(function () use (
            $test, $device, $contacts, $variantACount, $variantBCount,
            $holdoutCount, $variantA, $variantB, $decideAt, $userId
        ) {
            $offset = 0;

            // ── Create campaign + blasts for each variant ───────────────────
            foreach ([
                ['variant' => $variantA, 'count' => $variantACount],
                ['variant' => $variantB, 'count' => $variantBCount],
            ] as $chunk) {
                $v       = $chunk['variant'];
                $numbers = $contacts->slice($offset, $chunk['count']);
                $offset += $chunk['count'];

                $isTemplate   = $v->message_type === 'template';
                $tmpl         = $isTemplate ? $v->template : null;
                $campaignMsg  = $isTemplate
                    ? ['template_name' => $tmpl?->name, 'language' => $tmpl?->language]
                    : ['text' => $v->message_body];
                $blastMsg     = json_encode($campaignMsg);

                $campaign = Campaign::create([
                    'user_id'      => $userId,
                    'device_id'    => $device->id,
                    'phonebook_id' => $test->phonebook_id,
                    'name'         => "[AB Test #{$test->id}] {$test->name} — Variant {$v->label}",
                    'type'         => $isTemplate ? 'metatemplate' : 'text',
                    'message'      => $campaignMsg,
                    'template_id'  => $v->template_id,
                    'status'       => 'waiting',
                    'schedule'     => now(),
                    'delay'        => 1,
                ]);

                $blasts = $numbers->map(fn ($number) => [
                    'user_id'     => $userId,
                    'sender'      => $device->body,
                    'campaign_id' => $campaign->id,
                    'receiver'    => $number,
                    'type'        => $isTemplate ? 'template' : 'text',
                    'status'      => 'pending',
                    'message'     => $blastMsg,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ])->values()->all();

                Blast::insert($blasts);
                $v->update(['campaign_id' => $campaign->id]);
            }

            // ── Store holdout contacts ──────────────────────────────────────
            $holdouts = $contacts->slice($offset, $holdoutCount)->map(fn ($n) => [
                'ab_test_id'     => $test->id,
                'contact_number' => $n,
            ])->values()->all();

            if (!empty($holdouts)) {
                AbHoldoutContact::insert($holdouts);
            }

            // ── Update test status ──────────────────────────────────────────
            $test->update(['status' => 'running', 'decide_at' => $decideAt]);
        });

        // Dispatch winner decision job after N hours
        DecideAbWinnerJob::dispatch($test->id)
            ->delay($decideAt)
            ->onQueue('broadcasts');

        return redirect()->route('ab.show', $test->id)
            ->with('success', __('A/B test launched! Winner will be decided at :time.', [
                'time' => $decideAt->format('M d, H:i'),
            ]));
    }

    public function destroy(Request $request, int $id)
    {
        AbTest::where('user_id', $request->user()->id)->findOrFail($id)->delete();
        return redirect()->route('ab.index')->with('success', __('Test deleted.'));
    }
}
