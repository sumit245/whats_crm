<x-mail::message>
# ⚠️ WhatsApp Quality Rating Alert

Your WhatsApp Business number **{{ $phone }}** has had its quality rating degraded.

<x-mail::panel>
**Previous Rating:** {{ $oldRating }}
**New Rating:** {{ $newRating }}
</x-mail::panel>

## What This Means

@if($newRating === 'YELLOW')
Your number is now at **Medium Quality**. Meta may throttle your messaging tier if this continues. You should **pause all marketing broadcasts immediately** and review recent campaign feedback.
@elseif($newRating === 'RED')
Your number is now at **Low Quality**. Meta will likely throttle your messaging limits within 48 hours. **Stop all broadcasts now** and review opt-outs and complaint rates.
@else
Your quality rating has changed. Please review your recent campaigns and message content.
@endif

## Recommended Actions

1. **Pause all marketing broadcasts** on this number immediately
2. Review your recent message templates for compliance
3. Check your opt-out rate and message relevance
4. Wait 7 days before resuming high-volume broadcasting
5. Monitor your number's quality rating daily in the platform

<x-mail::button :url="url('/meta/health')">
View Number Health Dashboard
</x-mail::button>

**Device:** {{ $device->body }}
**Detected at:** {{ now()->format('Y-m-d H:i:s T') }}

Thanks,<br>
{{ config('app.name') }} Team
</x-mail::message>
