<div class="bubble-wrap {{ $msg->direction }}" data-id="{{ $msg->id }}">
    <div class="bubble {{ $msg->direction }}">
        @if ($msg->media_url)
            @if (in_array($msg->type, ['image', 'sticker']))
                <img src="{{ $msg->media_url }}" class="bubble-media" alt="image" onerror="this.style.display='none'">
            @else
                <a href="{{ $msg->media_url }}" target="_blank" class="d-flex align-items-center gap-1 text-decoration-none mb-1">
                    <i class="bi bi-file-earmark"></i>
                    <small>{{ $msg->type === 'document' ? __('Document') : ucfirst($msg->type) }}</small>
                </a>
            @endif
        @endif
        @if ($msg->body && !in_array($msg->body, ['[Image]','[Video]','[Audio]','[Document]','[Sticker]','[Location]','[Contact]']))
            {!! nl2br(e($msg->body)) !!}
        @elseif (!$msg->media_url)
            <em class="text-muted small">{{ $msg->body }}</em>
        @endif
        <div class="bubble-time">
            {{ $msg->created_at->format('H:i') }}
            @if ($msg->direction === 'outbound')
                @php
                    $tickCls = match($msg->status) { 'read' => 'read', 'delivered' => 'delivered', default => 'sent' };
                    $tick    = $msg->status === 'sent' ? '✓' : '✓✓';
                @endphp
                <span class="status-tick {{ $tickCls }}">{{ $tick }}</span>
            @endif
        </div>
    </div>
</div>
