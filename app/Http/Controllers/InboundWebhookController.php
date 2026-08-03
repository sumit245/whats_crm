<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessWebhookJob;
use App\Models\WebhookLog;
use App\Models\WebhookSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InboundWebhookController extends Controller
{
    /**
     * Receive an inbound webhook POST at /wh/{token}.
     * No auth, no CSRF — token in the URL is the secret.
     */
    public function receive(Request $request, string $token)
    {
        $source = WebhookSource::where('token', $token)->where('active', true)->first();

        if (!$source) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $payload = $request->json()->all();
        if (empty($payload)) {
            $payload = $request->all();
        }

        $headers = collect($request->headers->all())
            ->only(['content-type', 'x-shopify-topic', 'x-wc-webhook-event', 'x-razorpay-event-id', 'user-agent'])
            ->toArray();

        // Log the raw webhook immediately
        $log = WebhookLog::create([
            'webhook_source_id' => $source->id,
            'headers'           => $headers,
            'payload'           => $payload,
            'status'            => 'received',
        ]);

        // Find all active triggers that match this payload
        $fired = 0;
        foreach ($source->triggers()->where('active', true)->get() as $trigger) {
            if (!$trigger->matches($payload)) {
                continue;
            }

            // Update log with the first matching trigger (for simple sources)
            if ($fired === 0) {
                $log->update(['webhook_trigger_id' => $trigger->id]);
            }

            ProcessWebhookJob::dispatch($log->id, $trigger->id, $payload);
            $fired++;
        }

        if ($fired === 0) {
            $log->update(['status' => 'skipped', 'processed_at' => now(), 'error' => 'No matching triggers.']);
        }

        return response()->json(['received' => true, 'triggers_fired' => $fired]);
    }
}
