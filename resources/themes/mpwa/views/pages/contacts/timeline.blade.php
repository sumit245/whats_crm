<x-layout-dashboard title="{{ $contactName }} — {{ __('360° Profile') }}">

{{-- ── Header ──────────────────────────────────────────────────────────────── --}}
<div class="d-flex align-items-center gap-3 mb-4">
    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold"
         style="width:52px;height:52px;font-size:1.4rem;flex-shrink:0">
        {{ strtoupper(mb_substr($contactName, 0, 1)) }}
    </div>
    <div class="flex-grow-1">
        <h5 class="mb-0 fw-semibold">{{ $contactName }}</h5>
        <span class="text-muted small font-monospace">+{{ $number }}</span>
        @if($isSuppressed)
            <span class="badge bg-danger ms-2"><i class="bi bi-slash-circle me-1"></i>{{ __('Suppressed') }}</span>
        @endif
        @foreach($phonebooks as $pb)
            <span class="badge bg-secondary ms-1">{{ $pb->tag?->name ?? '—' }}</span>
        @endforeach
    </div>
    @php $latestConv = $conversations->first(); @endphp
    @if($latestConv)
        <a href="{{ route('chat.show', $latestConv->id) }}?conv_status={{ $latestConv->conversation_status }}"
           class="btn btn-sm btn-outline-primary">
            <i class="bi bi-chat-dots me-1"></i>{{ __('Open Chat') }}
        </a>
    @endif
</div>

{{-- ── Stats bar ────────────────────────────────────────────────────────────── --}}
<div class="row g-2 mb-4">
    @php
        $statCards = [
            ['label' => __('Campaigns Sent'), 'value' => $stats['campaigns_sent'], 'icon' => 'bi-broadcast',      'color' => 'primary'],
            ['label' => __('Messages Read'),  'value' => $stats['read_count'],      'icon' => 'bi-eye',            'color' => 'success'],
            ['label' => __('Link Clicks'),    'value' => $stats['total_clicks'],    'icon' => 'bi-cursor-fill',    'color' => 'danger'],
            ['label' => __('Conversations'),  'value' => $stats['conversations'],   'icon' => 'bi-chat-dots-fill', 'color' => 'info'],
        ];
    @endphp
    @foreach($statCards as $s)
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-3 d-flex align-items-center gap-3">
                <div class="rounded-circle bg-{{ $s['color'] }} bg-opacity-10 text-{{ $s['color'] }} d-flex align-items-center justify-content-center" style="width:40px;height:40px;flex-shrink:0">
                    <i class="bi {{ $s['icon'] }}"></i>
                </div>
                <div>
                    <div class="fw-bold fs-5 lh-1">{{ number_format($s['value']) }}</div>
                    <div class="text-muted" style="font-size:0.75rem">{{ $s['label'] }}</div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="row g-3">

    {{-- ── Left: profile sidebar ──────────────────────────────────────────── --}}
    <div class="col-12 col-lg-4">

        {{-- Custom attributes --}}
        @if(!empty($attributes))
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2 bg-transparent border-bottom">
                <span class="small fw-semibold"><i class="bi bi-tags me-1"></i>{{ __('Custom Attributes') }}</span>
            </div>
            <div class="card-body py-2 px-3">
                <dl class="row mb-0" style="font-size:0.82rem">
                    @foreach($attributes as $key => $val)
                    <dt class="col-5 text-muted fw-normal text-truncate">{{ $key }}</dt>
                    <dd class="col-7 mb-1 fw-semibold text-truncate" title="{{ $val }}">{{ $val ?: '—' }}</dd>
                    @endforeach
                </dl>
            </div>
        </div>
        @endif

        {{-- Phonebook memberships --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2 bg-transparent border-bottom">
                <span class="small fw-semibold"><i class="bi bi-telephone-fill me-1"></i>{{ __('Phonebooks') }}</span>
            </div>
            <div class="card-body py-2 px-3">
                @forelse($phonebooks as $pb)
                <div class="d-flex align-items-center gap-2 py-1 border-bottom" style="font-size:0.82rem">
                    <i class="bi bi-person-lines-fill text-muted"></i>
                    <span>{{ $pb->tag?->name ?? '—' }}</span>
                    <span class="ms-auto text-muted">{{ $pb->name }}</span>
                </div>
                @empty
                <div class="text-muted small py-1">{{ __('Not in any phonebook') }}</div>
                @endforelse
            </div>
        </div>

        {{-- Segment memberships --}}
        @if($segments->isNotEmpty())
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2 bg-transparent border-bottom">
                <span class="small fw-semibold"><i class="bi bi-funnel-fill me-1"></i>{{ __('Segments') }}</span>
            </div>
            <div class="card-body py-2 px-3">
                @foreach($segments as $seg)
                <span class="badge bg-info-subtle text-info me-1 mb-1">{{ $seg->name }}</span>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Open conversations --}}
        @if($conversations->isNotEmpty())
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-2 bg-transparent border-bottom">
                <span class="small fw-semibold"><i class="bi bi-chat-dots-fill me-1"></i>{{ __('Conversations') }}</span>
            </div>
            <div class="list-group list-group-flush" style="font-size:0.82rem">
                @foreach($conversations as $conv)
                <a href="{{ route('chat.show', $conv->id) }}" class="list-group-item list-group-item-action py-2 px-3">
                    <div class="d-flex justify-content-between">
                        <span class="fw-semibold">
                            @php $sc = match($conv->conversation_status) { 'open' => 'success', 'pending' => 'warning', 'resolved' => 'secondary', default => 'secondary' }; @endphp
                            <span class="badge bg-{{ $sc }} me-1" style="font-size:0.65rem">{{ ucfirst($conv->conversation_status) }}</span>
                            {{ $conv->assignedAgent?->name ?? __('Unassigned') }}
                        </span>
                        <span class="text-muted" style="font-size:0.7rem">{{ $conv->last_message_at?->diffForHumans() }}</span>
                    </div>
                    <div class="text-muted text-truncate mt-1">{{ $conv->last_message ?? '—' }}</div>
                    @if($conv->labels->isNotEmpty())
                    <div class="mt-1">
                        @foreach($conv->labels as $lbl)
                        <span class="badge" style="background:{{ $lbl->color }};font-size:0.65rem">{{ $lbl->name }}</span>
                        @endforeach
                    </div>
                    @endif
                </a>
                @endforeach
            </div>
        </div>
        @endif

    </div>

    {{-- ── Right: unified timeline ─────────────────────────────────────────── --}}
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header py-2 bg-transparent border-bottom d-flex align-items-center justify-content-between">
                <span class="small fw-semibold"><i class="bi bi-clock-history me-1"></i>{{ __('Activity Timeline') }}</span>
                <span class="text-muted small">{{ $timeline->count() }} {{ __('events') }}</span>
            </div>
            <div class="card-body p-0">
                @if($timeline->isEmpty())
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-clock-history d-block mb-2" style="font-size:2.5rem"></i>
                        {{ __('No activity recorded yet for this contact.') }}
                    </div>
                @else
                <div class="timeline-wrap px-3 pt-3 pb-1">
                    @foreach($timeline as $item)
                    @php
                        $typeConfig = match($item['type']) {
                            'blast'        => ['icon' => 'bi-broadcast',       'color' => 'primary'],
                            'click'        => ['icon' => 'bi-cursor-fill',     'color' => 'danger'],
                            'conversation' => ['icon' => 'bi-chat-dots-fill',  'color' => 'info'],
                            'note'         => ['icon' => 'bi-sticky-fill',     'color' => 'warning'],
                            default        => ['icon' => 'bi-circle',          'color' => 'secondary'],
                        };

                        $statusBadge = match($item['status'] ?? '') {
                            'read'      => ['bg-success',          __('Read')],
                            'delivered' => ['bg-info text-dark',   __('Delivered')],
                            'sent'      => ['bg-secondary',        __('Sent')],
                            'pending'   => ['bg-light text-dark',  __('Pending')],
                            'clicked'   => ['bg-danger',           __('Clicked')],
                            'open'      => ['bg-success',          __('Open')],
                            'resolved'  => ['bg-secondary',        __('Resolved')],
                            default     => ['',                    ''],
                        };
                    @endphp
                    <div class="d-flex gap-3 mb-3 timeline-item">
                        {{-- dot --}}
                        <div class="d-flex flex-column align-items-center" style="width:32px;flex-shrink:0">
                            <div class="rounded-circle bg-{{ $typeConfig['color'] }} bg-opacity-15 text-{{ $typeConfig['color'] }} d-flex align-items-center justify-content-center"
                                 style="width:32px;height:32px">
                                <i class="bi {{ $typeConfig['icon'] }}" style="font-size:0.8rem"></i>
                            </div>
                            <div class="flex-grow-1 border-start border-2 border-light mt-1" style="min-height:12px"></div>
                        </div>

                        {{-- content --}}
                        <div class="flex-grow-1 pb-2">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div>
                                    <span class="fw-semibold" style="font-size:0.88rem">{{ $item['title'] }}</span>
                                    @if($statusBadge[0])
                                        <span class="badge {{ $statusBadge[0] }} ms-1" style="font-size:0.65rem">{{ $statusBadge[1] }}</span>
                                    @endif
                                    @if($item['type'] === 'conversation' && $item['meta']['agent'])
                                        <span class="text-muted ms-1" style="font-size:0.75rem">· {{ $item['meta']['agent'] }}</span>
                                    @endif
                                    @if($item['type'] === 'note' && $item['meta']['author'])
                                        <span class="text-muted ms-1" style="font-size:0.75rem">· {{ $item['meta']['author'] }}</span>
                                    @endif
                                </div>
                                <span class="text-muted text-nowrap" style="font-size:0.72rem">
                                    {{ \Carbon\Carbon::parse($item['date'])->diffForHumans() }}
                                </span>
                            </div>

                            @if($item['subtitle'])
                            <div class="text-muted mt-1" style="font-size:0.8rem;word-break:break-word">
                                @if($item['type'] === 'click')
                                    <i class="bi bi-link-45deg me-1"></i><a href="{{ $item['meta']['url'] }}" target="_blank" rel="noopener" class="text-muted">{{ $item['subtitle'] }}</a>
                                @elseif($item['type'] === 'conversation')
                                    <a href="{{ route('chat.show', $item['meta']['conversation_id']) }}" class="text-muted text-decoration-none">{{ $item['subtitle'] }}</a>
                                @elseif($item['type'] === 'note')
                                    <a href="{{ route('chat.show', $item['meta']['conversation_id']) }}" class="text-muted text-decoration-none">{{ Str::limit($item['subtitle'], 120) }}</a>
                                @else
                                    {{ $item['subtitle'] }}
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>

</div>

<style>
.timeline-item:last-child .border-start { display:none !important; }
</style>

</x-layout-dashboard>
