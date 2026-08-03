<?php

namespace App\Http\Controllers;

use App\Models\Catalogue;
use App\Models\CatalogueProduct;
use App\Models\Device;
use App\Services\MetaCatalogueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogueController extends Controller
{
    // ── Pages ────────────────────────────────────────────────────────────────

    public function index(Request $request): View|JsonResponse
    {
        $user      = auth()->user();
        $devices   = $user->devices()->get();
        $device    = ($devices->firstWhere('id', session('selectedDevice')) ?? $devices->first());

        $catalogues = $device
            ? Catalogue::where('device_id', $device->id)->orderByDesc('updated_at')->get()
            : collect();

        // AJAX call from chat modal
        if ($request->ajax() || $request->has('json')) {
            return response()->json($catalogues->map(fn($c) => [
                'id'              => $c->id,
                'name'            => $c->name,
                'meta_catalog_id' => $c->meta_catalog_id,
                'product_count'   => $c->product_count,
                'is_linked'       => $c->is_linked,
            ]));
        }

        return view('theme::pages.catalogue.index', compact('devices', 'device', 'catalogues'));
    }

    public function show(int $id): View
    {
        $catalogue = Catalogue::where('id', $id)
            ->where('device_id', $this->userDeviceIds())
            ->firstOrFail();

        $products = $catalogue->products()->orderBy('name')->paginate(50);

        return view('theme::pages.catalogue.show', compact('catalogue', 'products'));
    }

    // ── Sync from Meta ───────────────────────────────────────────────────────

    public function sync(Request $request): JsonResponse
    {
        $device = $this->resolveDevice($request->device_id);
        if (!$device) return $this->err('No device found.');

        $svc        = new MetaCatalogueService($device);
        $businessId = $this->resolveBusinessId($device, $svc);
        if (!$businessId) return $this->err('Could not fetch Business ID from Meta. Check your access token permissions.');

        $data = $svc->getCatalogues($businessId);
        if (isset($data['error'])) return $this->err($data['error']);

        // Fetch which catalog is currently linked to this phone number
        $commerceSettings = $svc->getCommerceSettings();
        $linkedCatalogId  = $commerceSettings['catalog_id'] ?? null;

        $count = 0;
        foreach ($data as $item) {
            Catalogue::updateOrCreate(
                ['meta_catalog_id' => $item['id']],
                [
                    'device_id'     => $device->id,
                    'name'          => $item['name'],
                    'description'   => $item['description'] ?? null,
                    'vertical'      => $item['vertical'] ?? 'COMMERCE',
                    'product_count' => $item['product_count'] ?? 0,
                    'business_id'   => $businessId,
                    'is_linked'     => $item['id'] === $linkedCatalogId,
                    'synced_at'     => now(),
                ]
            );
            $count++;
        }

        return response()->json(['status' => true, 'count' => $count, 'msg' => "{$count} catalogue(s) synced."]);
    }

    public function syncProducts(int $id): JsonResponse
    {
        $catalogue = Catalogue::where('id', $id)
            ->whereIn('device_id', $this->userDeviceIds())
            ->firstOrFail();

        $device = $catalogue->device;
        $svc    = new MetaCatalogueService($device);
        $data   = $svc->getProducts($catalogue->meta_catalog_id);

        if (isset($data['error'])) return $this->err($data['error']);

        $count = 0;
        foreach ($data as $item) {
            CatalogueProduct::updateOrCreate(
                ['catalogue_id' => $catalogue->id, 'retailer_id' => $item['retailer_id']],
                [
                    'name'         => $item['name'],
                    'description'  => $item['description'] ?? null,
                    'price'        => $item['price'] ?? 0,
                    'sale_price'   => $item['sale_price'] ?? null,
                    'currency'     => $item['currency'] ?? null,
                    'image_url'    => $item['image_url'] ?? null,
                    'product_url'  => $item['url'] ?? null,
                    'availability' => $item['availability'] ?? 'in stock',
                    'condition'    => $item['condition'] ?? 'new',
                    'brand'        => $item['brand'] ?? null,
                    'category'     => $item['category'] ?? null,
                ]
            );
            $count++;
        }

        $catalogue->update(['product_count' => $count, 'synced_at' => now()]);

        return response()->json(['status' => true, 'count' => $count, 'msg' => "{$count} product(s) synced."]);
    }

    // ── Create catalogue ─────────────────────────────────────────────────────

    public function createCatalogue(Request $request): JsonResponse
    {
        $request->validate([
            'device_id'   => 'required|integer',
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'vertical'    => 'nullable|string',
        ]);

        $device = $this->resolveDevice($request->device_id);
        if (!$device) return $this->err('No device found.');

        $svc        = new MetaCatalogueService($device);
        $businessId = $this->resolveBusinessId($device, $svc);
        if (!$businessId) return $this->err('Could not fetch Business ID. Check your access token has catalog_management permission.');

        $result = $svc->createCatalogue($businessId, $request->name, $request->description ?? '', $request->vertical ?? 'COMMERCE');
        if (isset($result['error'])) return $this->err($result['error']);

        $catalogue = Catalogue::create([
            'device_id'       => $device->id,
            'meta_catalog_id' => $result['id'],
            'name'            => $request->name,
            'description'     => $request->description,
            'vertical'        => $request->vertical ?? 'COMMERCE',
            'business_id'     => $businessId,
            'synced_at'       => now(),
        ]);

        return response()->json(['status' => true, 'catalogue' => $catalogue, 'msg' => 'Catalogue created successfully.']);
    }

    public function destroyCatalogue(int $id): JsonResponse
    {
        $catalogue = Catalogue::where('id', $id)
            ->whereIn('device_id', $this->userDeviceIds())
            ->firstOrFail();

        $svc = new MetaCatalogueService($catalogue->device);
        $svc->deleteCatalogue($catalogue->meta_catalog_id);
        $catalogue->delete();

        return response()->json(['status' => true, 'msg' => 'Catalogue deleted.']);
    }

    // ── Link to device ───────────────────────────────────────────────────────

    public function linkCatalogue(Request $request, int $id): JsonResponse
    {
        $catalogue = Catalogue::where('id', $id)
            ->whereIn('device_id', $this->userDeviceIds())
            ->firstOrFail();

        $svc    = new MetaCatalogueService($catalogue->device);
        $result = $svc->linkCatalogue(
            $catalogue->meta_catalog_id,
            (bool) $request->boolean('cart_enabled', true),
            (bool) $request->boolean('catalog_visible', true),
        );

        if (isset($result['error'])) return $this->err($result['error']);

        // Update is_linked flag locally
        Catalogue::where('device_id', $catalogue->device_id)->update(['is_linked' => false]);
        $catalogue->update(['is_linked' => true]);

        return response()->json(['status' => true, 'msg' => 'Catalogue linked to device.']);
    }

    // ── Products CRUD ────────────────────────────────────────────────────────

    public function createProduct(Request $request, int $id): JsonResponse
    {
        $catalogue = Catalogue::where('id', $id)
            ->whereIn('device_id', $this->userDeviceIds())
            ->firstOrFail();

        $request->validate([
            'retailer_id'  => 'required|string|max:100',
            'name'         => 'required|string|max:200',
            'description'  => 'nullable|string',
            'price'        => 'required|numeric|min:0',
            'currency'     => 'required|string|size:3',
            'image_url'    => 'required|url',
            'product_url'  => 'nullable|url',
            'availability' => 'nullable|in:in stock,out of stock,preorder,available for order,discontinued',
            'condition'    => 'nullable|in:new,refurbished,used',
            'brand'        => 'nullable|string|max:100',
            'category'     => 'nullable|string|max:100',
            'sale_price'   => 'nullable|numeric|min:0',
        ]);

        $svc    = new MetaCatalogueService($catalogue->device);
        $result = $svc->createProduct($catalogue->meta_catalog_id, $request->all());
        if (isset($result['error'])) return $this->err($result['error']);

        $product = CatalogueProduct::create([
            'catalogue_id'  => $catalogue->id,
            'retailer_id'   => $request->retailer_id,
            'name'          => $request->name,
            'description'   => $request->description,
            'price'         => (int) round($request->price * 100),
            'sale_price'    => $request->sale_price ? (int) round($request->sale_price * 100) : null,
            'currency'      => strtoupper($request->currency),
            'image_url'     => $request->image_url,
            'product_url'   => $request->product_url,
            'availability'  => $request->availability ?? 'in stock',
            'condition'     => $request->condition ?? 'new',
            'brand'         => $request->brand,
            'category'      => $request->category,
        ]);

        $catalogue->increment('product_count');

        return response()->json(['status' => true, 'product' => $product, 'msg' => 'Product added successfully.']);
    }

    public function updateProduct(Request $request, int $productId): JsonResponse
    {
        $product   = CatalogueProduct::findOrFail($productId);
        $catalogue = Catalogue::whereIn('device_id', $this->userDeviceIds())->findOrFail($product->catalogue_id);

        $svc    = new MetaCatalogueService($catalogue->device);
        $result = $svc->updateProduct($product->retailer_id, $request->all());
        if (isset($result['error'])) return $this->err($result['error']);

        $product->update(array_filter([
            'name'         => $request->name,
            'description'  => $request->description,
            'price'        => $request->price ? (int) round($request->price * 100) : null,
            'sale_price'   => $request->sale_price ? (int) round($request->sale_price * 100) : null,
            'currency'     => $request->currency ? strtoupper($request->currency) : null,
            'image_url'    => $request->image_url,
            'product_url'  => $request->product_url,
            'availability' => $request->availability,
            'condition'    => $request->condition,
            'brand'        => $request->brand,
            'category'     => $request->category,
        ], fn($v) => $v !== null));

        return response()->json(['status' => true, 'msg' => 'Product updated.']);
    }

    public function destroyProduct(int $productId): JsonResponse
    {
        $product   = CatalogueProduct::findOrFail($productId);
        $catalogue = Catalogue::whereIn('device_id', $this->userDeviceIds())->findOrFail($product->catalogue_id);

        $svc = new MetaCatalogueService($catalogue->device);
        $svc->deleteProduct($product->retailer_id);
        $product->delete();
        $catalogue->decrement('product_count');

        return response()->json(['status' => true, 'msg' => 'Product deleted.']);
    }

    // ── Chat picker AJAX ─────────────────────────────────────────────────────

    public function productsJson(int $id): JsonResponse
    {
        $catalogue = Catalogue::where('id', $id)
            ->whereIn('device_id', $this->userDeviceIds())
            ->firstOrFail();

        $products = $catalogue->products()
            ->select('id', 'retailer_id', 'name', 'price', 'currency', 'image_url')
            ->orderBy('name')
            ->get()
            ->map(fn($p) => [
                'id'          => $p->id,
                'retailer_id' => $p->retailer_id,
                'name'        => $p->name,
                'price'       => $p->price_decimal,
                'currency'    => $p->currency,
                'image_url'   => $p->image_url,
            ]);

        return response()->json($products);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function userDeviceIds(): array
    {
        return auth()->user()->devices()->pluck('id')->toArray();
    }

    private function resolveDevice(?int $deviceId): ?Device
    {
        $user = auth()->user();
        if ($deviceId) {
            return $user->devices()->find($deviceId);
        }
        $selected = session('selectedDevice');
        return $selected ? $user->devices()->find($selected) : $user->devices()->first();
    }

    private function resolveBusinessId(Device $device, MetaCatalogueService $svc): ?string
    {
        // Use cached value if present
        if ($device->business_id ?? false) return $device->business_id;

        $businessId = $svc->fetchBusinessId();
        if ($businessId) {
            $device->update(['business_id' => $businessId]);
        }
        return $businessId;
    }

    private function err(string $msg): JsonResponse
    {
        return response()->json(['status' => false, 'msg' => $msg], 422);
    }
}
