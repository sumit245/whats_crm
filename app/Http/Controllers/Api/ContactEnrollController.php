<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ContactAttribute;
use App\Models\Device;
use App\Models\DripEnrollment;
use App\Models\DripSequence;
use App\Models\SuppressionEntry;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ContactEnrollController extends Controller
{
    // ── Auth helper ──────────────────────────────────────────────────────────

    private function resolveUser(Request $request): ?User
    {
        $apiKey = $request->input('api_key') ?? $request->bearerToken();
        if (!$apiKey) return null;

        return Cache::remember('api_user_' . $apiKey, 3600, fn () =>
            User::where('api_key', $apiKey)->first()
        );
    }

    private function fail(string $message, int $status = 400): JsonResponse
    {
        return response()->json(['status' => false, 'message' => $message], $status);
    }

    // ── POST /api/contacts/enroll ────────────────────────────────────────────

    public function enroll(Request $request): JsonResponse
    {
        $user = $this->resolveUser($request);
        if (!$user) return $this->fail('Invalid api_key.', 401);

        $number = trim((string) $request->input('number', ''));
        if (!$number) return $this->fail('number is required.');

        // Normalise: strip + and spaces
        $number = preg_replace('/\D/', '', $number);
        if (strlen($number) < 7) return $this->fail('Invalid number format.');

        $result = [
            'number'              => $number,
            'name'                => null,
            'phonebook'           => null,
            'suppression_removed' => false,
            'drip_enrolled'       => false,
            'attributes_saved'    => 0,
        ];

        // ── Resolve phonebook ────────────────────────────────────────────────
        $phonebook = null;
        if ($pbId = $request->input('phonebook_id')) {
            $phonebook = Tag::where('user_id', $user->id)->find($pbId);
        } elseif ($pbName = $request->input('phonebook_name')) {
            $phonebook = Tag::firstOrCreate(
                ['user_id' => $user->id, 'name' => trim($pbName)],
                ['user_id' => $user->id, 'name' => trim($pbName)]
            );
        }

        // ── Create / update contact ──────────────────────────────────────────
        $name    = trim((string) $request->input('name', ''));
        $contact = Contact::where('user_id', $user->id)->where('number', $number)->first();

        if ($contact) {
            if ($name) $contact->update(['name' => $name]);
        } else {
            $contact = Contact::create([
                'user_id' => $user->id,
                'number'  => $number,
                'name'    => $name ?: $number,
            ]);
        }
        if ($phonebook) {
            $contact->tags()->syncWithoutDetaching([$phonebook->id]);
        }

        $result['name']      = $contact->name;
        $result['phonebook'] = $phonebook?->name;

        // ── Remove from suppression list if requested ────────────────────────
        if ($request->boolean('remove_suppression', true)) {
            $deleted = SuppressionEntry::where('user_id', $user->id)
                ->where('number', $number)->delete();
            $result['suppression_removed'] = (bool) $deleted;
        }

        // ── Save custom attributes ───────────────────────────────────────────
        $attributes = $request->input('attributes', []);
        if (is_array($attributes) && !empty($attributes)) {
            foreach ($attributes as $key => $value) {
                ContactAttribute::setFor($user->id, $number, (string) $key, (string) $value);
            }
            $result['attributes_saved'] = count($attributes);
        }

        // ── Enroll in drip sequence ──────────────────────────────────────────
        if ($seqId = $request->input('drip_sequence_id')) {
            $sequence = DripSequence::where('user_id', $user->id)
                ->where('status', 'active')
                ->with('steps')
                ->find($seqId);

            if ($sequence) {
                $deviceId = $this->resolveDeviceId($request, $user);
                if ($deviceId) {
                    $enrollment = $sequence->enroll($number, $user->id, $deviceId);
                    $result['drip_enrolled'] = (bool) $enrollment;
                } else {
                    $result['drip_enrolled'] = false;
                    $result['drip_error']    = 'device_id required for drip enrollment';
                }
            } else {
                $result['drip_enrolled'] = false;
                $result['drip_error']    = 'Sequence not found or not active';
            }
        }

        return response()->json([
            'status'  => true,
            'message' => 'Contact enrolled successfully.',
            'data'    => $result,
        ]);
    }

    // ── POST /api/contacts/unenroll ──────────────────────────────────────────

    public function unenroll(Request $request): JsonResponse
    {
        $user = $this->resolveUser($request);
        if (!$user) return $this->fail('Invalid api_key.', 401);

        $number = preg_replace('/\D/', '', (string) $request->input('number', ''));
        if (!$number) return $this->fail('number is required.');

        $result = ['number' => $number, 'removed_from_phonebook' => false,
                   'drip_cancelled' => 0, 'suppressed' => false];

        // Remove from phonebook(s) — unlinks only, the CRM contact record itself is kept
        $contact = Contact::where('user_id', $user->id)->where('number', $number)->first();
        if ($contact && ($pbId = $request->input('phonebook_id'))) {
            $result['removed_from_phonebook'] = (bool) $contact->tags()->detach($pbId);
        } elseif ($contact && $request->boolean('remove_all_phonebooks')) {
            $result['removed_from_phonebook'] = (bool) $contact->tags()->detach();
        }

        // Cancel active drip enrollments
        if ($request->boolean('cancel_drip', true)) {
            $result['drip_cancelled'] = DripEnrollment::where('user_id', $user->id)
                ->where('contact_number', $number)
                ->where('status', 'active')
                ->update(['status' => 'cancelled', 'next_run_at' => null]);
        }

        // Suppress contact
        if ($request->boolean('suppress')) {
            SuppressionEntry::suppress($user->id, $number, 'api_unenroll', 'Unenrolled via API');
            $result['suppressed'] = true;
        }

        return response()->json(['status' => true, 'message' => 'Contact unenrolled.', 'data' => $result]);
    }

    // ── POST /api/contacts/attributes ───────────────────────────────────────

    public function setAttributes(Request $request): JsonResponse
    {
        $user = $this->resolveUser($request);
        if (!$user) return $this->fail('Invalid api_key.', 401);

        $number = preg_replace('/\D/', '', (string) $request->input('number', ''));
        if (!$number) return $this->fail('number is required.');

        $attributes = $request->input('attributes', []);
        if (!is_array($attributes) || empty($attributes)) {
            return $this->fail('attributes must be a non-empty object.');
        }

        foreach ($attributes as $key => $value) {
            ContactAttribute::setFor($user->id, $number, (string) $key, (string) $value);
        }

        return response()->json([
            'status'  => true,
            'message' => count($attributes) . ' attribute(s) saved.',
            'data'    => ['number' => $number, 'saved' => count($attributes)],
        ]);
    }

    private function resolveDeviceId(Request $request, User $user): ?int
    {
        if ($id = $request->input('device_id')) {
            return (int) $id;
        }
        // Fall back to user's first device
        return Device::where('user_id', $user->id)->value('id');
    }
}
