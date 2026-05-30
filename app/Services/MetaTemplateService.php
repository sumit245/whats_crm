<?php

namespace App\Services;

use App\Models\Device;
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
