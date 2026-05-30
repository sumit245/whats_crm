<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Services\Impl\MetaCloudApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $numbers = $request->user()->devices()->latest()->paginate(15);

        $user = $request->user()
            ->withCount(['devices', 'campaigns'])
            ->withCount(['blasts as blasts_pending' => fn ($q) => $q->where('status', 'pending')])
            ->withCount(['blasts as blasts_success'  => fn ($q) => $q->where('status', 'success')])
            ->withCount(['blasts as blasts_failed'   => fn ($q) => $q->where('status', 'failed')])
            ->withCount('messageHistories')
            ->find($request->user()->id);

        if ($request->user()->level === 'admin') {
            $user['subscription_status'] = __('Admin');
        } else {
            $user['subscription_status'] = $user->isExpiredSubscription ? 'Expired' : $user->active_subscription;
        }

        $user['expired_subscription_status'] = $user->expiredSubscription;

        return view('theme::home', compact('numbers', 'user'));
    }

    public function store(Request $request)
    {
        $validate = validator($request->all(), [
            'sender'          => 'required|min:3|max:30|unique:devices,body',
            'phone_number_id' => 'required|string',
            'waba_id'         => 'required|string',
            'access_token'    => 'required|string',
        ]);

        if ($request->user()->isExpiredSubscription && $request->user()->level !== 'admin') {
            return back()->with('alert', ['type' => 'danger', 'msg' => __('Your subscription has expired, please renew your subscription.')]);
        }

        if ($validate->fails()) {
            return back()->with('alert', ['type' => 'danger', 'msg' => $validate->errors()->first()]);
        }

        if ($request->user()->limit_device <= $request->user()->devices()->count() && $request->user()->level !== 'admin') {
            return back()->with('alert', ['type' => 'danger', 'msg' => __('You have reached the limit of devices!')]);
        }

        // Build a temporary device object to verify credentials
        $tempDevice = new Device([
            'phone_number_id' => $request->phone_number_id,
            'access_token'    => $request->access_token,
            'waba_id'         => $request->waba_id,
        ]);

        $service = new MetaCloudApiService($tempDevice);
        $result  = $service->connectDevice($tempDevice);

        if (!$result->status) {
            return back()->with('alert', ['type' => 'danger', 'msg' => __('Invalid Meta credentials: ') . ($result->error ?? '')]);
        }

        $profile = $result->data;

        $request->user()->devices()->create([
            'body'             => $request->sender,
            'phone_number_id'  => $request->phone_number_id,
            'waba_id'          => $request->waba_id,
            'access_token'     => $request->access_token,
            'webhook'          => $request->urlwebhook,
            'status'           => 'Connected',
            'quality_rating'   => $profile['quality_rating']['display_quality_rating'] ?? 'GREEN',
            'messaging_tier'   => $profile['messaging_limit_tier'] ?? null,
            'meta_profile'     => [
                'verified_name'       => $profile['verified_name'] ?? $request->sender,
                'display_phone_number' => $profile['display_phone_number'] ?? $request->sender,
                'platform_type'       => $profile['platform_type'] ?? null,
            ],
        ]);

        return back()->with('alert', ['type' => 'success', 'msg' => __('Device connected successfully!')]);
    }

    public function destroy(Request $request)
    {
        try {
            $device = $request->user()->devices()->find($request->deviceId);
            if (!$device) {
                return back()->with('alert', ['type' => 'danger', 'msg' => __('Device not found!')]);
            }
            Session::forget('selectedDevice');
            $device->delete();
            return back()->with('alert', ['type' => 'success', 'msg' => __('Device removed!')]);
        } catch (\Throwable $th) {
            return back()->with('alert', ['type' => 'danger', 'msg' => __('Something went wrong!')]);
        }
    }

    public function setSelectedDeviceSession(Request $request)
    {
        $device = $request->user()->devices()->find($request->device);
        if (!$device) {
            Session::forget('selectedDevice');
            return response()->json(['error' => true, 'msg' => __('Device not found!')]);
        }
        session()->put('selectedDevice', [
            'device_id'   => $device->id,
            'device_body' => $device->body,
        ]);
        return response()->json(['error' => false, 'msg' => __('Device selected!')]);
    }

    public function setHook(Request $request)
    {
        $request->user()->devices()->whereBody($request->number)->update(['webhook' => $request->webhook]);
        return response()->json(['error' => false, 'msg' => __('Webhook updated.')]);
    }
}
