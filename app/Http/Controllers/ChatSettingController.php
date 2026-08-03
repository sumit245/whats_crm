<?php

namespace App\Http\Controllers;

use App\Models\ChatSetting;
use DateTimeZone;
use Illuminate\Http\Request;

class ChatSettingController extends Controller
{
    public function show(Request $request)
    {
        $setting   = ChatSetting::forUser($request->user()->id);
        $timezones = DateTimeZone::listIdentifiers();
        $days      = [
            'mon' => __('Monday'),
            'tue' => __('Tuesday'),
            'wed' => __('Wednesday'),
            'thu' => __('Thursday'),
            'fri' => __('Friday'),
            'sat' => __('Saturday'),
            'sun' => __('Sunday'),
        ];

        // Ensure all 7 days exist in the hours array
        $hours = $setting->working_hours ?? ChatSetting::defaultWorkingHours();
        foreach ($days as $key => $_) {
            $hours[$key] ??= ['enabled' => false, 'start' => '09:00', 'end' => '18:00'];
        }

        return view('theme::pages.chat.settings', compact('setting', 'timezones', 'days', 'hours'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'timezone'            => 'required|timezone',
            'off_hours_enabled'   => 'boolean',
            'off_hours_message'   => 'nullable|string|max:1000',
            'auto_resolve_enabled'=> 'boolean',
            'auto_resolve_hours'  => 'required|integer|min:1|max:720',
        ]);

        $days = ['mon','tue','wed','thu','fri','sat','sun'];
        $hours = [];
        foreach ($days as $day) {
            $hours[$day] = [
                'enabled' => $request->boolean("hours_{$day}_enabled"),
                'start'   => $request->input("hours_{$day}_start", '09:00'),
                'end'     => $request->input("hours_{$day}_end",   '18:00'),
            ];
        }

        $setting = ChatSetting::forUser($request->user()->id);
        $setting->update([
            'timezone'             => $request->timezone,
            'working_hours'        => $hours,
            'off_hours_enabled'    => $request->boolean('off_hours_enabled'),
            'off_hours_message'    => $request->off_hours_message,
            'auto_resolve_enabled' => $request->boolean('auto_resolve_enabled'),
            'auto_resolve_hours'   => (int) $request->auto_resolve_hours,
        ]);

        return back()->with('success', __('Chat settings saved.'));
    }
}
