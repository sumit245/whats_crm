<?php
/*
Copyright © Magd Almuntaser, OneXGen Technology. All rights reserved.
Project: MPWA Whatsapp Gateway | Multi Device
Licensed under the CC BY-NC-ND 4.0 License.
For details, visit https://creativecommons.org/licenses/by-nc-nd/4.0/.
*/

namespace App\Http\Controllers;

use App\Models\Plans;
use Illuminate\Http\Request;

class PlansController extends Controller
{
    /**
     * The feature flags (besides the numeric limits) stored on a plan.
     *
     * @var array<int, string>
     */
    protected array $featureKeys = [
        'ai_message',
        'schedule_message',
        'bulk_message',
        'autoreply',
        'send_message',
        'send_media',
        'send_list',
        'send_template',
        'send_button',
        'send_location',
        'send_poll',
        'send_sticker',
        'send_vcard',
        'webhook',
        'api',
    ];

    /**
     * List all plans.
     */
    public function index()
    {
        $plans = Plans::orderBy('price')->get();

        return view('theme::pages.admin.plans.index', compact('plans'));
    }

    /**
     * The create form is handled by a modal on the index page.
     */
    public function create()
    {
        return redirect()->route('admin.plans.index');
    }

    /**
     * Store a new plan.
     */
    public function store(Request $request)
    {
        $validated = $this->validatePlan($request);

        Plans::create($this->planAttributes($request, $validated));

        return backWithFlash('success', __('Plan created successfully'));
    }

    /**
     * Update an existing plan.
     */
    public function update(Request $request, Plans $plan)
    {
        $validated = $this->validatePlan($request);

        $plan->update($this->planAttributes($request, $validated));

        return backWithFlash('success', __('Plan updated successfully'));
    }

    /**
     * Delete a plan.
     */
    public function destroy(Plans $plan)
    {
        $plan->delete();

        return backWithFlash('success', __('Plan deleted successfully'));
    }

    /**
     * Validate the incoming plan request.
     *
     * @return array<string, mixed>
     */
    protected function validatePlan(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'symbol' => 'required|string|max:10',
            'is_recommended' => 'required|in:0,1',
            'status' => 'required|in:0,1',
            'days' => 'required|integer|min:0',
            'trial_days' => 'required|integer|min:0',
            'messages_limit' => 'required|integer|min:0',
            'device_limit' => 'required|integer|min:0',
        ]);
    }

    /**
     * Build the persisted plan attributes from the request.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function planAttributes(Request $request, array $validated): array
    {
        $submitted = (array) $request->input('data', []);

        $data = [
            'messages_limit' => (int) $request->input('messages_limit'),
            'device_limit' => (int) $request->input('device_limit'),
        ];

        foreach ($this->featureKeys as $key) {
            $data[$key] = isset($submitted[$key])
                ? filter_var($submitted[$key], FILTER_VALIDATE_BOOLEAN)
                : false;
        }

        return [
            'title' => $validated['title'],
            'price' => $validated['price'],
            'symbol' => $validated['symbol'],
            'is_recommended' => (int) $validated['is_recommended'],
            'status' => (int) $validated['status'],
            'days' => (int) $validated['days'],
            'trial_days' => (int) $validated['trial_days'],
            // NOTE: The admin form has no explicit "is_trial" toggle, so we
            // derive it from whether a trial period is configured.
            'is_trial' => (int) $validated['trial_days'] > 0 ? 1 : 0,
            'data' => $data,
        ];
    }
}
