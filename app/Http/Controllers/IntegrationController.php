<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;

class IntegrationController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $canCustomApp = $user->level === 'admin' || !empty($user->plan_data['integration_custom_app']);
        $canWidget    = $user->level === 'admin' || !empty($user->plan_data['integration_website_widget']);

        return view('theme::pages.integrations.index', compact('canCustomApp', 'canWidget'));
    }

    public function customApp(): View
    {
        $this->authorizeFeature('integration_custom_app');

        $user    = auth()->user();
        $devices = $user->devices()->latest()->get();
        $apiKey  = $user->api_key;
        $baseUrl = rtrim(config('app.url'), '/') . '/' . app()->getLocale();

        return view('theme::pages.integrations.custom-app', compact('user', 'devices', 'apiKey', 'baseUrl'));
    }

    public function widget(): View
    {
        $this->authorizeFeature('integration_website_widget');

        $user    = auth()->user();
        $devices = $user->devices()->latest()->get();

        return view('theme::pages.integrations.widget', compact('devices'));
    }

    public function activateWidget(Request $request): JsonResponse
    {
        $this->authorizeFeature('integration_website_widget');

        $request->validate(['device_id' => 'required|integer']);

        $device = Device::where('id', $request->device_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if (empty($device->widget_token)) {
            $device->widget_token = Str::uuid()->toString();
            $device->save();
        }

        $widgetUrl = route('widget.serve', ['token' => $device->widget_token]);

        return response()->json([
            'token'      => $device->widget_token,
            'widget_url' => $widgetUrl,
            'embed_code' => '<script src="' . $widgetUrl . '" async></script>',
        ]);
    }

    public function configureWidget(Request $request): JsonResponse
    {
        $this->authorizeFeature('integration_website_widget');

        $data = $request->validate([
            'device_id'      => 'required|integer',
            'color'          => 'required|string|max:20',
            'position'       => 'required|in:bottom-right,bottom-left',
            'button_text'    => 'required|string|max:60',
            'prefill'        => 'nullable|string|max:200',
            'show_tooltip'   => 'boolean',
            'tooltip_text'   => 'nullable|string|max:80',
        ]);

        $device = Device::where('id', $data['device_id'])
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $device->widget_config = [
            'color'        => $data['color'],
            'position'     => $data['position'],
            'button_text'  => $data['button_text'],
            'prefill'      => $data['prefill'] ?? '',
            'show_tooltip' => (bool) ($data['show_tooltip'] ?? true),
            'tooltip_text' => $data['tooltip_text'] ?? 'Chat with us on WhatsApp',
        ];
        $device->save();

        return response()->json(['success' => true]);
    }

    public function serveWidget(string $token): Response
    {
        $device = Device::where('widget_token', $token)->first();

        if (! $device) {
            abort(404);
        }

        $cfg = array_merge([
            'color'        => '#25D366',
            'position'     => 'bottom-right',
            'button_text'  => 'Chat with us',
            'prefill'      => '',
            'show_tooltip' => true,
            'tooltip_text' => 'Chat with us on WhatsApp',
        ], $device->widget_config ?? []);

        // Strip non-numeric chars so the phone number is safe for wa.me
        $phone = preg_replace('/\D/', '', $device->body);

        $js = view('theme::widget.script', compact('cfg', 'phone'))->render();

        return response($js, 200, ['Content-Type' => 'application/javascript; charset=UTF-8']);
    }

    private function authorizeFeature(string $key): void
    {
        $user = auth()->user();
        if ($user->level === 'admin') {
            return;
        }
        if (env('ENABLE_INDEX') === 'yes' && empty($user->plan_data[$key])) {
            abort(redirect()->route('permission.denied'));
        }
    }
}
