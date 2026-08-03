<?php

namespace App\Services;

use App\Models\Device;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaTemplateService
{
    protected string $graphBase = 'https://graph.facebook.com/v20.0';

    public function createTemplate(Device $device, array $payload): array
    {
        try {
            $response = Http::withToken($device->access_token)
                ->post("{$this->graphBase}/{$device->waba_id}/message_templates", $payload);

            if ($response->failed()) {
                return ['success' => false, 'error' => $response->json('error.message', 'Unknown error')];
            }

            return ['success' => true, 'data' => $response->json()];
        } catch (\Throwable $e) {
            Log::error('MetaTemplateService::createTemplate', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function fetchTemplates(Device $device): array
    {
        $templates = [];
        $url = "{$this->graphBase}/{$device->waba_id}/message_templates";
        $params = [
            'fields' => 'id,name,status,category,language,components,rejected_reason',
            'limit'  => 100,
        ];

        try {
            do {
                $response = Http::withToken($device->access_token)->get($url, $params);

                if ($response->failed()) {
                    Log::error('MetaTemplateService::fetchTemplates', ['error' => $response->body()]);
                    break;
                }

                $data = $response->json();
                $templates = array_merge($templates, $data['data'] ?? []);

                // Follow pagination
                $url = $data['paging']['next'] ?? null;
                $params = []; // next URL already contains params
            } while ($url);
        } catch (\Throwable $e) {
            Log::error('MetaTemplateService::fetchTemplates', ['error' => $e->getMessage()]);
        }

        return $templates;
    }

    /**
     * Fetch a single template's current status directly from Meta API.
     * Uses the numeric meta_template_id (e.g. "12345678").
     */
    public function fetchSingleTemplate(Device $device, string $metaTemplateId): ?array
    {
        try {
            $response = Http::withToken($device->access_token)
                ->get("{$this->graphBase}/{$metaTemplateId}", [
                    'fields' => 'id,name,status,category,language,components,rejected_reason',
                ]);

            if ($response->failed()) {
                Log::error('MetaTemplateService::fetchSingleTemplate', [
                    'template_id' => $metaTemplateId,
                    'error'       => $response->body(),
                ]);
                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('MetaTemplateService::fetchSingleTemplate', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Fetch Meta's pre-approved template library.
     * Uses the global /message_template_library endpoint (no WABA prefix).
     * Returns all pages merged into a flat array.
     */
    // Cache TTL for the library — 6 hours. Meta's catalog changes rarely.
    private const LIBRARY_CACHE_TTL = 6 * 3600;

    public function fetchLibraryTemplates(Device $device, ?string $category = null, ?string $language = null): array
    {
        // Cache key is independent of the device (library is global), keyed by filters only
        $cacheKey = 'meta_tpl_library_' . md5(($category ?? '') . '_' . ($language ?? ''));

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return ['data' => $cached, 'error' => null, 'cached' => true];
        }

        $templates = [];
        $url    = "{$this->graphBase}/message_template_library";
        $params = [
            'fields' => 'id,name,language,category,topic,usecase,industry,components',
            'limit'  => 200,
        ];
        if ($category) $params['category'] = $category;
        if ($language) $params['language']  = $language;

        try {
            do {
                $response = Http::withToken($device->access_token)
                    ->timeout(20)
                    ->get($url, $params);

                if ($response->failed()) {
                    $errMsg = $response->json('error.error_user_msg')
                           ?? $response->json('error.message')
                           ?? 'Meta API error (HTTP '.$response->status().')';
                    Log::error('MetaTemplateService::fetchLibraryTemplates', [
                        'status' => $response->status(),
                        'error'  => $response->body(),
                    ]);
                    return ['error' => $errMsg, 'data' => []];
                }

                $data = $response->json();
                foreach ($data['data'] ?? [] as $raw) {
                    $templates[] = $this->normalizeLibraryTemplate($raw);
                }
                $url    = $data['paging']['next'] ?? null;
                $params = [];
            } while ($url);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('MetaTemplateService::fetchLibraryTemplates timeout', ['error' => $e->getMessage()]);
            return ['error' => 'Connection to Meta timed out. Please try again.', 'data' => []];
        } catch (\Throwable $e) {
            Log::error('MetaTemplateService::fetchLibraryTemplates', ['error' => $e->getMessage()]);
            return ['error' => $e->getMessage(), 'data' => []];
        }

        Cache::put($cacheKey, $templates, self::LIBRARY_CACHE_TTL);

        return ['data' => $templates, 'error' => null, 'cached' => false];
    }

    public function clearLibraryCache(): void
    {
        // Clear all library cache keys (pattern-based)
        Cache::forget('meta_tpl_library_' . md5('_'));
        // Also clear common combinations
        foreach (['', 'MARKETING', 'UTILITY', 'AUTHENTICATION'] as $cat) {
            foreach (['', 'en_US', 'en', 'hi', 'ar', 'es', 'pt_BR', 'fr', 'de', 'id'] as $lang) {
                Cache::forget('meta_tpl_library_' . md5($cat . '_' . $lang));
            }
        }
    }

    /**
     * Flatten a raw library template API item into our storage shape.
     * Meta returns content inside a `components` array; we extract each part.
     */
    private function normalizeLibraryTemplate(array $raw): array
    {
        $header  = null;
        $body    = null;
        $footer  = null;
        $buttons = [];

        foreach ($raw['components'] ?? [] as $component) {
            $type = strtoupper($component['type'] ?? '');
            switch ($type) {
                case 'HEADER':
                    $header = $component['text'] ?? null;
                    break;
                case 'BODY':
                    $body = $component['text'] ?? null;
                    break;
                case 'FOOTER':
                    $footer = $component['text'] ?? null;
                    break;
                case 'BUTTONS':
                    $buttons = $component['buttons'] ?? [];
                    break;
            }
        }

        return [
            'id'       => $raw['id']       ?? null,
            'name'     => $raw['name']     ?? '',
            'language' => $raw['language'] ?? 'en',
            'category' => $raw['category'] ?? 'MARKETING',
            'topic'    => is_string($raw['topic']   ?? null) ? $raw['topic']   : null,
            'usecase'  => is_string($raw['usecase'] ?? null) ? $raw['usecase'] : null,
            'industry' => is_string($raw['industry']?? null) ? $raw['industry']: null,
            'header'   => $header,
            'body'     => $body,
            'footer'   => $footer,
            'buttons'  => $buttons,
        ];
    }

    /**
     * Add a library template to the WABA.
     *
     * For templates WITHOUT URL buttons: use library_template_name for instant add.
     * For templates WITH URL buttons: build full components from library fields and
     * POST as a regular template (user must supply their real URL — Meta's placeholder
     * `https://www.example.com` is not a valid production URL).
     */
    public function addFromLibrary(Device $device, array $libraryTemplate, ?string $customUrl = null): array
    {
        $hasUrlButton = collect($libraryTemplate['buttons'] ?? [])
            ->contains('type', 'URL');

        try {
            if (!$hasUrlButton) {
                // Simple path: library_template_name does all the work
                $response = Http::withToken($device->access_token)
                    ->timeout(20)
                    ->post("{$this->graphBase}/{$device->waba_id}/message_templates", [
                        'library_template_name' => $libraryTemplate['name'],
                        'name'                  => $libraryTemplate['name'],
                        'category'              => $libraryTemplate['category'],
                        'language'              => $libraryTemplate['language'],
                    ]);
            } else {
                // URL button path: build components manually so we can swap in the real URL
                $components = $this->buildComponentsFromLibrary($libraryTemplate, $customUrl);
                $response = Http::withToken($device->access_token)
                    ->timeout(20)
                    ->post("{$this->graphBase}/{$device->waba_id}/message_templates", [
                        'name'       => $libraryTemplate['name'],
                        'category'   => $libraryTemplate['category'],
                        'language'   => $libraryTemplate['language'],
                        'components' => $components,
                    ]);
            }

            if ($response->failed()) {
                return ['success' => false, 'error' => $response->json('error.error_user_msg') ?? $response->json('error.message', 'Unknown error')];
            }

            return ['success' => true, 'data' => $response->json()];
        } catch (\Throwable $e) {
            Log::error('MetaTemplateService::addFromLibrary', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Build a components array from flat library template fields.
     */
    private function buildComponentsFromLibrary(array $t, ?string $customUrl): array
    {
        $components = [];

        if (!empty($t['header'])) {
            $components[] = ['type' => 'HEADER', 'format' => 'TEXT', 'text' => $t['header']];
        }

        if (!empty($t['body'])) {
            $comp = ['type' => 'BODY', 'text' => $t['body']];
            // Count {{N}} variables and supply generic examples
            preg_match_all('/\{\{(\d+)\}\}/', $t['body'], $m);
            if (!empty($m[1])) {
                $comp['example'] = ['body_text' => [array_fill(0, count($m[1]), 'example')]];
            }
            $components[] = $comp;
        }

        if (!empty($t['footer'])) {
            $components[] = ['type' => 'FOOTER', 'text' => $t['footer']];
        }

        if (!empty($t['buttons'])) {
            $buttons = array_map(function ($btn) use ($customUrl) {
                if ($btn['type'] === 'URL') {
                    $url = $customUrl ?: $btn['url'];
                    $b   = ['type' => 'URL', 'text' => $btn['text'], 'url' => $url];
                    if (str_contains($url, '{{1}}')) {
                        $b['example'] = ['https://example.com/path'];
                    }
                    return $b;
                }
                return $btn;
            }, $t['buttons']);

            $components[] = ['type' => 'BUTTONS', 'buttons' => $buttons];
        }

        return $components;
    }

    public function deleteTemplate(Device $device, string $name): bool
    {
        try {
            $response = Http::withToken($device->access_token)
                ->delete("{$this->graphBase}/{$device->waba_id}/message_templates", ['name' => $name]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('MetaTemplateService::deleteTemplate', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
