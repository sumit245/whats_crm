@php
if (!function_exists('waFmt')):
function waFmt(string $text): string {
    // Escape first, then apply formatting markers
    $text = e($text);
    // Bold: *text* (no space inside)
    $text = preg_replace('/\*([^*\n]+)\*/', '<strong>$1</strong>', $text);
    // Italic: _text_
    $text = preg_replace('/_([^_\n]+)_/', '<em>$1</em>', $text);
    // Strikethrough: ~text~
    $text = preg_replace('/~([^~\n]+)~/', '<del>$1</del>', $text);
    // Monospace: `text`
    $text = preg_replace('/`([^`\n]+)`/', '<code style="font-family:monospace;font-size:.9em;background:rgba(0,0,0,.07);border-radius:3px;padding:0 3px">$1</code>', $text);
    // Newlines
    $text = nl2br($text);
    return $text;
}
endif;

$hiddenBodies = ['[Image]','[Video]','[Audio]','[Document]','[Sticker]','[Location]','[Contact]'];
$isTemplate   = $msg->type === 'template';
$isPoll       = $msg->type === 'poll';
$isMeeting    = $msg->type === 'meeting_link';
$isCatalog    = $msg->type === 'catalog';
$isContact    = $msg->type === 'contact';
$isLocation   = $msg->type === 'location';

// Extract template name from body like "[Template: name]" for the header chip
$templateName = null;
if ($isTemplate && preg_match('/^\[Template: (.+)\]$/', $msg->body ?? '', $m)) {
    $templateName = $m[1];
}
@endphp
<div class="bubble-wrap {{ $msg->direction }}" data-id="{{ $msg->id }}">
    <div class="bubble {{ $msg->direction }}">

        {{-- ── Media (image / sticker) ───────────────── --}}
        @if ($msg->media_url && in_array($msg->type, ['image', 'sticker']))
            <img src="{{ $msg->media_url }}" class="bubble-media" alt="image" onerror="this.style.display='none'">
        @endif

        {{-- ── Video ─────────────────────────────────── --}}
        @if ($msg->media_url && $msg->type === 'video')
            <video src="{{ $msg->media_url }}" controls class="bubble-media" style="border-radius:6px;max-width:100%"></video>
        @endif

        {{-- ── Audio ─────────────────────────────────── --}}
        @if ($msg->media_url && $msg->type === 'audio')
            <audio src="{{ $msg->media_url }}" controls style="width:220px;max-width:100%"></audio>
        @endif

        {{-- ── Document ───────────────────────────────── --}}
        @if ($msg->media_url && $msg->type === 'document')
            <a href="{{ $msg->media_url }}" target="_blank"
               class="d-flex align-items-center gap-2 text-decoration-none mb-1 p-2"
               style="background:rgba(0,0,0,.05);border-radius:8px;">
                <i class="bi bi-file-earmark-text" style="font-size:22px;flex-shrink:0"></i>
                <div>
                    <div style="font-size:12px;font-weight:600">{{ __('Document') }}</div>
                    <div style="font-size:10px;opacity:.6">{{ __('Tap to open') }}</div>
                </div>
                <i class="bi bi-download ms-auto" style="font-size:13px;opacity:.5"></i>
            </a>
        @endif

        {{-- ── Template card ──────────────────────────── --}}
        @if ($isTemplate)
            @if ($templateName)
                {{-- Stored as old [Template: name] format — show a card --}}
                <div class="d-flex align-items-center gap-2 mb-1 px-1">
                    <i class="bi bi-layout-text-sidebar-reverse" style="font-size:14px;opacity:.6"></i>
                    <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;opacity:.6">{{ __('Template') }}</span>
                    <span class="badge rounded-pill" style="font-size:10px;background:rgba(0,0,0,.08);color:inherit;font-weight:600">{{ $templateName }}</span>
                </div>
            @else
                {{-- Body has actual content — just show the template chip above --}}
                <div class="d-flex align-items-center gap-1 mb-1" style="opacity:.55;font-size:11px">
                    <i class="bi bi-layout-text-sidebar-reverse"></i>
                    <span style="font-weight:600;text-transform:uppercase;letter-spacing:.4px">{{ __('Template') }}</span>
                </div>
                <div>{!! waFmt($msg->body ?? '') !!}</div>
            @endif
        @elseif ($isPoll)
            {{-- Poll card --}}
            <div class="d-flex align-items-center gap-1 mb-1" style="opacity:.55;font-size:11px">
                <i class="bi bi-bar-chart-line"></i>
                <span style="font-weight:600;text-transform:uppercase;letter-spacing:.4px">{{ __('Poll') }}</span>
            </div>
            @if ($msg->body && !in_array($msg->body, $hiddenBodies))
                <div>{!! waFmt($msg->body) !!}</div>
            @endif
        @elseif ($isMeeting)
            {{-- Meeting link card --}}
            <div class="d-flex align-items-center gap-1 mb-1" style="opacity:.55;font-size:11px">
                <i class="bi bi-camera-video"></i>
                <span style="font-weight:600;text-transform:uppercase;letter-spacing:.4px">{{ __('Meeting Link') }}</span>
            </div>
            @if ($msg->body && !in_array($msg->body, $hiddenBodies))
                <div>{!! waFmt($msg->body) !!}</div>
            @endif
        @elseif ($isCatalog)
            {{-- Catalog card --}}
            <div class="d-flex align-items-center gap-1 mb-1" style="opacity:.55;font-size:11px">
                <i class="bi bi-shop"></i>
                <span style="font-weight:600;text-transform:uppercase;letter-spacing:.4px">{{ __('Catalogue') }}</span>
            </div>
            @if ($msg->body && !in_array($msg->body, $hiddenBodies))
                <div>{!! waFmt($msg->body) !!}</div>
            @endif
        @elseif ($isContact)
            {{-- Contact card --}}
            <div class="d-flex align-items-center gap-2" style="background:rgba(0,0,0,.05);border-radius:8px;padding:8px 10px">
                <i class="bi bi-person-circle" style="font-size:28px;flex-shrink:0;opacity:.5"></i>
                <div>
                    @if ($msg->body && !in_array($msg->body, $hiddenBodies))
                        <div style="font-weight:600;font-size:14px">{!! waFmt($msg->body) !!}</div>
                    @else
                        <div style="opacity:.5;font-size:12px">{{ __('Contact') }}</div>
                    @endif
                </div>
            </div>
        @elseif ($isLocation)
            {{-- Location card --}}
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="bi bi-geo-alt-fill" style="font-size:18px;color:#dc3545"></i>
                <span style="font-size:12px;font-weight:600">{{ __('Location') }}</span>
            </div>
            @if ($msg->body && !in_array($msg->body, $hiddenBodies))
                <div style="font-size:12px;opacity:.7">{!! waFmt($msg->body) !!}</div>
            @endif
        @elseif (!$msg->media_url || ($msg->body && !in_array($msg->body, $hiddenBodies)))
            {{-- Regular text (with WhatsApp formatting) --}}
            @if ($msg->body && !in_array($msg->body, $hiddenBodies))
                <div>{!! waFmt($msg->body) !!}</div>
            @elseif (!$msg->media_url)
                <em class="text-muted small">{{ $msg->body }}</em>
            @endif
        @endif

        {{-- Caption under media --}}
        @if ($msg->media_url && $msg->body && !in_array($msg->body, $hiddenBodies) && !in_array($msg->type, ['document']))
            <div class="mt-1" style="font-size:13px">{!! waFmt($msg->body) !!}</div>
        @endif

        <div class="bubble-time">
            {{ $msg->created_at->format('H:i') }}
            @if ($msg->direction === 'outbound')
                @if ($msg->status === 'failed')
                    <span class="status-tick" style="color:#dc3545" title="{{ __('Delivery failed — contact may be outside 24h window. Use a template.') }}">✕</span>
                @else
                    @php
                        $tickCls = match($msg->status) { 'read' => 'read', 'delivered' => 'delivered', default => '' };
                        $tick    = $msg->status === 'sent' ? '✓' : '✓✓';
                    @endphp
                    <span class="status-tick {{ $tickCls }}">{{ $tick }}</span>
                @endif
            @endif
        </div>
    </div>
</div>
