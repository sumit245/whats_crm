<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\LibraryTemplate;
use App\Models\WabaTemplate;
use App\Services\MetaTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TemplateController extends Controller
{
    public function __construct(protected MetaTemplateService $templateService) {}

    public function index(Request $request)
    {
        $templates = WabaTemplate::where('user_id', $request->user()->id)
            ->with('device')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->category, fn ($q) => $q->where('category', $request->category))
            ->when($request->device_id, fn ($q) => $q->where('device_id', $request->device_id))
            ->latest()
            ->paginate(20);

        $devices = $request->user()->devices()->where('status', 'Connected')->get();

        return view('theme::pages.templates.index', compact('templates', 'devices'));
    }

    public function create(Request $request)
    {
        $devices = $request->user()->devices()->where('status', 'Connected')->get();
        return view('theme::pages.templates.create', compact('devices'));
    }

    public function show(Request $request, $id)
    {
        $template = WabaTemplate::where('user_id', $request->user()->id)->findOrFail($id);
        return response()->json($template);
    }

    public function store(Request $request)
    {
        $request->validate([
            'device_id'  => 'required|exists:devices,id',
            'name'       => ['required', 'regex:/^[a-z0-9_]+$/', 'max:512'],
            'category'   => 'required|in:MARKETING,UTILITY,AUTHENTICATION',
            'language'   => 'required|string|max:10',
            'components' => 'required|json',
        ]);

        $device = $request->user()->devices()->findOrFail($request->device_id);
        $components = json_decode($request->components, true);

        $payload = [
            'name'       => $request->name,
            'category'   => $request->category,
            'language'   => $request->language,
            'components' => $components,
        ];

        $result = $this->templateService->createTemplate($device, $payload);

        if (!$result['success']) {
            return response()->json(['error' => true, 'message' => $result['error']], 422);
        }

        $metaData = $result['data'];

        WabaTemplate::create([
            'user_id'          => $request->user()->id,
            'device_id'        => $device->id,
            'name'             => $request->name,
            'meta_template_id' => $metaData['id'] ?? null,
            'category'         => $request->category,
            'language'         => $request->language,
            'status'           => strtoupper($metaData['status'] ?? 'PENDING'),
            'components'       => $components,
            'meta_synced_at'   => now(),
        ]);

        return response()->json(['error' => false, 'message' => __('Template submitted for Meta approval. Status: ') . ($metaData['status'] ?? 'PENDING')]);
    }

    public function sync(Request $request)
    {
        // Sync a specific device if given, otherwise sync ALL connected devices for this user.
        $devices = $request->filled('device_id')
            ? $request->user()->devices()->where('id', $request->device_id)->get()
            : $request->user()->devices()->where('status', 'Connected')->get();

        if ($devices->isEmpty()) {
            return response()->json(['error' => true, 'message' => __('No connected devices found.')], 422);
        }

        $synced  = 0;
        $updated = 0;

        foreach ($devices as $device) {
            $metaTemplates = $this->templateService->fetchTemplates($device);

            foreach ($metaTemplates as $mt) {
                $existing = WabaTemplate::where('meta_template_id', $mt['id'])
                    ->where('user_id', $request->user()->id)
                    ->first();

                $oldStatus = $existing?->status;
                $newStatus = strtoupper($mt['status'] ?? 'PENDING');

                $template = WabaTemplate::updateOrCreate(
                    ['meta_template_id' => $mt['id'], 'user_id' => $request->user()->id],
                    [
                        'device_id'        => $device->id,
                        'name'             => $mt['name'],
                        'category'         => $mt['category'],
                        'language'         => $mt['language'] ?? 'en',
                        'status'           => $newStatus,
                        'components'       => $mt['components'] ?? [],
                        'rejection_reason' => $mt['rejected_reason'] ?? null,
                        'meta_synced_at'   => now(),
                    ]
                );

                $synced++;

                // Fire notification if status actually changed
                if ($oldStatus && $oldStatus !== $newStatus) {
                    \App\Models\TemplateStatusNotification::create([
                        'user_id'       => $request->user()->id,
                        'template_id'   => $template->id,
                        'template_name' => $template->name,
                        'old_status'    => $oldStatus,
                        'new_status'    => $newStatus,
                        'rejection_reason' => $mt['rejected_reason'] ?? null,
                    ]);
                    $updated++;
                }
            }
        }

        $msg = __('Synced :n templates from Meta.', ['n' => $synced]);
        if ($updated > 0) {
            $msg .= ' ' . __(':n status changes detected.', ['n' => $updated]);
        }

        return response()->json(['error' => false, 'message' => $msg, 'synced' => $synced, 'updated' => $updated]);
    }

    /**
     * Instantly refresh a single template's status from Meta API.
     * Called by the per-row "Refresh" button — no page reload needed.
     */
    public function refreshStatus(Request $request, $id)
    {
        $template = WabaTemplate::where('user_id', $request->user()->id)->findOrFail($id);
        $device   = $template->device;

        if (!$device || !$device->access_token) {
            return response()->json(['error' => true, 'message' => __('Device has no API credentials.')], 422);
        }

        if (!$template->meta_template_id) {
            return response()->json(['error' => true, 'message' => __('Template has no Meta ID yet.')], 422);
        }

        $metaData = $this->templateService->fetchSingleTemplate($device, $template->meta_template_id);

        if (!$metaData) {
            return response()->json(['error' => true, 'message' => __('Could not reach Meta API. Check device credentials.')], 503);
        }

        $oldStatus = $template->status;
        $newStatus = strtoupper($metaData['status'] ?? $oldStatus);

        $template->update([
            'status'           => $newStatus,
            'rejection_reason' => $metaData['rejected_reason'] ?? $template->rejection_reason,
            'components'       => $metaData['components'] ?? $template->components,
            'meta_synced_at'   => now(),
        ]);

        // Notify if status changed
        if ($oldStatus !== $newStatus) {
            \App\Models\TemplateStatusNotification::create([
                'user_id'          => $request->user()->id,
                'template_id'      => $template->id,
                'template_name'    => $template->name,
                'old_status'       => $oldStatus,
                'new_status'       => $newStatus,
                'rejection_reason' => $metaData['rejected_reason'] ?? null,
            ]);
        }

        return response()->json([
            'error'      => false,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed'    => $oldStatus !== $newStatus,
            'message'    => $oldStatus !== $newStatus
                ? __('Status updated: :old → :new', ['old' => $oldStatus, 'new' => $newStatus])
                : __('Status confirmed: :status (no change)', ['status' => $newStatus]),
        ]);
    }

    // ── Template Library (full page) ─────────────────────────────────────────

    public function library(Request $request)
    {
        $user     = auth()->user();
        $devices  = $user->devices()->get();

        $category = $request->category;
        $language = $request->language;
        $industry = $request->industry;
        $usecase  = $request->usecase;
        $search   = $request->search;

        $templates = LibraryTemplate::query()
            ->category($category)
            ->language($language)
            ->industry($industry)
            ->usecase($usecase)
            ->search($search)
            ->orderBy('category')
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        $lastSync  = LibraryTemplate::max('synced_at');
        $total     = LibraryTemplate::count();

        // Distinct values for filter dropdowns (only non-null)
        $industries = LibraryTemplate::whereNotNull('industry')
            ->distinct()->orderBy('industry')->pluck('industry');
        $usecases   = LibraryTemplate::whereNotNull('usecase')
            ->distinct()->orderBy('usecase')->pluck('usecase');

        return view('theme::pages.templates.library', compact(
            'devices', 'templates', 'category', 'language', 'industry',
            'usecase', 'search', 'lastSync', 'total', 'industries', 'usecases'
        ));
    }

    public function librarySync(Request $request)
    {
        $request->validate(['device_id' => 'required|exists:devices,id']);
        $device = $request->user()->devices()->findOrFail($request->device_id);

        // Always hit Meta fresh (user explicitly requested sync)
        $this->templateService->clearLibraryCache();

        $result = $this->templateService->fetchLibraryTemplates(
            $device,
            $request->category ?: null,
            $request->language  ?: null,
        );

        if ($result['error']) {
            return response()->json(['error' => true, 'message' => $result['error']], 502);
        }

        $synced = now();
        $count  = 0;

        foreach ($result['data'] as $item) {
            LibraryTemplate::updateOrCreate(
                ['name' => $item['name'], 'language' => $item['language']],
                [
                    'category'  => $item['category']  ?? 'MARKETING',
                    'topic'     => $item['topic']      ?? null,
                    'usecase'   => $item['usecase']    ?? null,
                    'industry'  => $item['industry']   ?? null,
                    'header'    => $item['header']     ?? null,
                    'body'      => $item['body']       ?? null,
                    'footer'    => $item['footer']     ?? null,
                    'buttons'   => $item['buttons']    ?? [],
                    'meta_id'   => $item['id']         ?? null,
                    'synced_at' => $synced,
                ]
            );
            $count++;
        }

        return response()->json([
            'error'   => false,
            'count'   => $count,
            'message' => __(':count templates synced to library.', ['count' => $count]),
        ]);
    }

    public function libraryFetch(Request $request)
    {
        $request->validate(['device_id' => 'required|exists:devices,id']);
        $device = $request->user()->devices()->findOrFail($request->device_id);

        if ($request->boolean('refresh')) {
            $this->templateService->clearLibraryCache();
        }

        $result = $this->templateService->fetchLibraryTemplates(
            $device,
            $request->category ?: null,
            $request->language  ?: null,
        );

        if ($result['error']) {
            return response()->json(['error' => true, 'message' => $result['error']], 502);
        }

        return response()->json([
            'data'   => $result['data'],
            'cached' => $result['cached'] ?? false,
            'count'  => count($result['data']),
        ]);
    }

    public function addFromLibrary(Request $request)
    {
        $request->validate([
            'device_id'       => 'required|exists:devices,id',
            'template'        => 'required|array',
            'template.name'   => 'required|string',
            'template.language' => 'required|string|max:10',
            'template.category' => 'required|string',
            'custom_url'      => 'nullable|url',
        ]);

        $device   = $request->user()->devices()->findOrFail($request->device_id);
        $tpl      = $request->template;
        $result   = $this->templateService->addFromLibrary($device, $tpl, $request->custom_url);

        if (!$result['success']) {
            return response()->json(['error' => true, 'message' => $result['error']], 422);
        }

        $metaData = $result['data'];

        $template = WabaTemplate::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'name'    => $metaData['name'] ?? $tpl['name'],
                'device_id' => $device->id,
            ],
            [
                'meta_template_id' => $metaData['id'] ?? null,
                'category'         => $metaData['category'] ?? $tpl['category'],
                'language'         => $tpl['language'],
                'status'           => strtoupper($metaData['status'] ?? 'PENDING'),
                'components'       => $metaData['components'] ?? [],
                'meta_synced_at'   => now(),
            ]
        );

        return response()->json([
            'error'   => false,
            'message' => __('Template ":name" submitted — status: :status', [
                'name'   => $template->name,
                'status' => $template->status,
            ]),
            'template' => [
                'id'     => $template->id,
                'name'   => $template->name,
                'status' => $template->status,
            ],
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $template = WabaTemplate::where('user_id', $request->user()->id)->findOrFail($id);
        $device   = $template->device;

        $deleted = $this->templateService->deleteTemplate($device, $template->name);

        if (!$deleted) {
            // Still delete locally even if Meta call fails (template may already be deleted there)
            Log::warning("Meta template delete failed for {$template->name}, removing locally anyway.");
        }

        $template->delete();

        return response()->json(['error' => false, 'message' => __('Template deleted.')]);
    }
}
