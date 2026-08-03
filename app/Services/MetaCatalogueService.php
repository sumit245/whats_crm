<?php

namespace App\Services;

use App\Models\Device;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaCatalogueService
{
    private string $base = 'https://graph.facebook.com/v20.0';

    public function __construct(private Device $device) {}

    // ── Business ID ──────────────────────────────────────────────────────────

    public function fetchBusinessId(): ?string
    {
        $res = Http::withToken($this->device->access_token)
            ->get("{$this->base}/{$this->device->waba_id}", ['fields' => 'id,name,owner']);

        if ($res->failed()) {
            Log::error('MetaCatalogue: fetch business_id failed', ['body' => $res->body()]);
            return null;
        }

        return $res->json('owner.id');
    }

    // ── Catalogues ───────────────────────────────────────────────────────────

    public function getCatalogues(string $businessId): array
    {
        $res = Http::withToken($this->device->access_token)
            ->get("{$this->base}/{$businessId}/owned_product_catalogs", [
                'fields' => 'id,name,description,vertical,product_count',
            ]);

        if ($res->failed()) {
            Log::error('MetaCatalogue: list catalogues failed', ['body' => $res->body()]);
            return ['error' => $res->json('error.message', 'Failed to fetch catalogues')];
        }

        return $res->json('data', []);
    }

    public function createCatalogue(string $businessId, string $name, string $description = '', string $vertical = 'COMMERCE'): array
    {
        $res = Http::withToken($this->device->access_token)
            ->post("{$this->base}/{$businessId}/owned_product_catalogs", [
                'name'        => $name,
                'description' => $description,
                'vertical'    => $vertical,
            ]);

        if ($res->failed()) {
            return ['error' => $res->json('error.message', 'Failed to create catalogue')];
        }

        return $res->json();
    }

    public function deleteCatalogue(string $catalogId): bool
    {
        $res = Http::withToken($this->device->access_token)
            ->delete("{$this->base}/{$catalogId}");

        return $res->successful();
    }

    // ── Commerce settings (link/unlink) ──────────────────────────────────────

    public function getCommerceSettings(): array
    {
        $res = Http::withToken($this->device->access_token)
            ->get("{$this->base}/{$this->device->phone_number_id}/whatsapp_commerce_settings");

        if ($res->failed()) {
            return [];
        }

        return $res->json('data.0', []);
    }

    public function linkCatalogue(string $catalogId, bool $cartEnabled = true, bool $catalogVisible = true): array
    {
        $res = Http::withToken($this->device->access_token)
            ->post("{$this->base}/{$this->device->phone_number_id}/whatsapp_commerce_settings", [
                'catalog_id'         => $catalogId,
                'is_cart_enabled'    => $cartEnabled,
                'is_catalog_visible' => $catalogVisible,
            ]);

        if ($res->failed()) {
            return ['error' => $res->json('error.message', 'Failed to link catalogue')];
        }

        return $res->json();
    }

    // ── Products ─────────────────────────────────────────────────────────────

    public function getProducts(string $catalogId, int $limit = 100): array
    {
        $fields = 'retailer_id,name,description,price,sale_price,currency,image_url,url,availability,condition,brand,category';

        $res = Http::withToken($this->device->access_token)
            ->get("{$this->base}/{$catalogId}/products", [
                'fields' => $fields,
                'limit'  => $limit,
            ]);

        if ($res->failed()) {
            Log::error('MetaCatalogue: list products failed', ['body' => $res->body()]);
            return ['error' => $res->json('error.message', 'Failed to fetch products')];
        }

        return $res->json('data', []);
    }

    public function createProduct(string $catalogId, array $data): array
    {
        $payload = [
            'retailer_id'  => $data['retailer_id'],
            'name'         => $data['name'],
            'description'  => $data['description'] ?? '',
            'price'        => (int) round(($data['price'] ?? 0) * 100), // convert to minor units
            'currency'     => strtoupper($data['currency'] ?? 'INR'),
            'image_url'    => $data['image_url'] ?? '',
            'url'          => $data['product_url'] ?? '',
            'availability' => $data['availability'] ?? 'in stock',
            'condition'    => $data['condition'] ?? 'new',
        ];

        if (!empty($data['sale_price'])) {
            $payload['sale_price'] = (int) round($data['sale_price'] * 100);
        }
        if (!empty($data['brand']))    { $payload['brand']    = $data['brand']; }
        if (!empty($data['category'])) { $payload['category'] = $data['category']; }

        $res = Http::withToken($this->device->access_token)
            ->post("{$this->base}/{$catalogId}/products", $payload);

        if ($res->failed()) {
            return ['error' => $res->json('error.message', 'Failed to create product')];
        }

        return $res->json();
    }

    public function updateProduct(string $productId, array $data): array
    {
        $payload = array_filter([
            'name'         => $data['name'] ?? null,
            'description'  => $data['description'] ?? null,
            'price'        => isset($data['price']) ? (int) round($data['price'] * 100) : null,
            'currency'     => isset($data['currency']) ? strtoupper($data['currency']) : null,
            'image_url'    => $data['image_url'] ?? null,
            'url'          => $data['product_url'] ?? null,
            'availability' => $data['availability'] ?? null,
            'condition'    => $data['condition'] ?? null,
            'brand'        => $data['brand'] ?? null,
            'category'     => $data['category'] ?? null,
        ], fn($v) => $v !== null);

        if (isset($data['sale_price'])) {
            $payload['sale_price'] = (int) round($data['sale_price'] * 100);
        }

        $res = Http::withToken($this->device->access_token)
            ->post("{$this->base}/{$productId}", $payload);

        if ($res->failed()) {
            return ['error' => $res->json('error.message', 'Failed to update product')];
        }

        return $res->json();
    }

    public function deleteProduct(string $productId): bool
    {
        $res = Http::withToken($this->device->access_token)
            ->delete("{$this->base}/{$productId}");

        return $res->successful();
    }
}
