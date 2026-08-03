<x-layout-dashboard title="{{ __('Chatbot Flows') }}">

    <x-page-header
        title="{{ __('Chatbot flows') }}"
        subtitle="{{ __('Automate WhatsApp conversations with visual drag-and-drop flows') }}"
        :breadcrumb="[__('Chatbot Flows')]">
        <a href="{{ route('flows.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> {{ __('New flow') }}
        </a>
    </x-page-header>

<div class="flows-page">

    {{-- Stats strip --}}
    @if($flows->total() > 0)
    <div class="flows-stats">
        <div class="flow-stat">
            <div class="flow-stat-value">{{ $flows->total() }}</div>
            <div class="flow-stat-label">{{ __('Total flows') }}</div>
        </div>
        <div class="flow-stat">
            <div class="flow-stat-value">{{ $flows->where('status','active')->count() }}</div>
            <div class="flow-stat-label">{{ __('Active') }}</div>
        </div>
        <div class="flow-stat">
            <div class="flow-stat-value">{{ $flows->sum('sessions_count') }}</div>
            <div class="flow-stat-label">{{ __('Total sessions') }}</div>
        </div>
        <div class="flow-stat">
            <div class="flow-stat-value">{{ $flows->where('trigger_type','keyword')->count() }}</div>
            <div class="flow-stat-label">{{ __('Keyword triggers') }}</div>
        </div>
    </div>
    @endif

    {{-- Grid --}}
    <div class="flows-grid">
        @forelse ($flows as $flow)
        <div class="flow-card" onclick="window.location='{{ route('flows.edit', $flow->id) }}'">

            {{-- Top --}}
            <div class="fc-top">
                <span class="fc-status {{ $flow->status }}">
                    <span class="dot"></span>
                    {{ $flow->status === 'active' ? __('Active') : __('Draft') }}
                </span>
                <div class="dropdown" onclick="event.stopPropagation()">
                    <button class="fc-menu-btn" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-three-dots"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:0.82rem;min-width:160px">
                        <li>
                            <a class="dropdown-item" href="{{ route('flows.edit', $flow->id) }}">
                                <i class="bi bi-pencil-fill me-1"></i>{{ __('Edit') }}
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ route('flows.analytics', $flow->id) }}">
                                <i class="bi bi-bar-chart-fill me-1"></i>{{ __('Analytics') }}
                            </a>
                        </li>
                        <li>
                            <button class="dropdown-item btn-duplicate-flow" data-id="{{ $flow->id }}">
                                <i class="bi bi-files me-1"></i>{{ __('Duplicate') }}
                            </button>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <button class="dropdown-item text-danger btn-delete-flow" data-id="{{ $flow->id }}">
                                <i class="bi bi-trash me-1"></i>{{ __('Delete') }}
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Name + desc --}}
            <div>
                <p class="fc-name">{{ $flow->name }}</p>
                @if($flow->description)
                    <p class="fc-desc mt-1">{{ Str::limit($flow->description, 72) }}</p>
                @endif
            </div>

            {{-- Trigger --}}
            <div>
                @php
                    $triggerIcon  = ['keyword'=>'bi-lightning-fill','all'=>'bi-infinity','referral'=>'bi-cursor-fill','api'=>'bi-link-45deg'][$flow->trigger_type] ?? 'bi-lightning-fill';
                    $triggerLabel = ['keyword'=>__('Keyword'),'all'=>__('All messages'),'referral'=>__('Ad click'),'api'=>__('API webhook')][$flow->trigger_type] ?? $flow->trigger_type;
                @endphp
                <span class="fc-trigger">
                    <i class="bi {{ $triggerIcon }}"></i>
                    {{ $triggerLabel }}
                    @if($flow->trigger_value)
                        <span style="color:#94a3b8">·</span>
                        <span style="font-family:monospace;font-size:0.72rem">{{ Str::limit($flow->trigger_value, 22) }}</span>
                    @endif
                </span>
            </div>

            {{-- Metrics --}}
            <div class="fc-metrics">
                <div class="fc-metric">
                    <span class="fc-metric-val">{{ $flow->sessions_count }}</span>
                    <span class="fc-metric-lbl">{{ __('Sessions') }}</span>
                </div>
                <div class="fc-metric">
                    <span class="fc-metric-val">{{ $flow->device->meta_profile['verified_name'] ?? Str::limit($flow->device->body ?? '—', 14) }}</span>
                    <span class="fc-metric-lbl">{{ __('Device') }}</span>
                </div>
                <div class="fc-metric">
                    <span class="fc-metric-val">{{ $flow->updated_at->diffForHumans(null, true) }}</span>
                    <span class="fc-metric-lbl">{{ __('Updated') }}</span>
                </div>
            </div>

            {{-- Actions --}}
            <div class="fc-actions" onclick="event.stopPropagation()">
                <button class="fc-toggle {{ $flow->status === 'active' ? 'is-active' : 'is-draft' }} btn-toggle-flow"
                    data-id="{{ $flow->id }}" data-status="{{ $flow->status }}"
                    title="{{ $flow->status === 'active' ? __('Click to deactivate') : __('Click to activate') }}">
                    <i class="bi {{ $flow->status === 'active' ? 'bi-pause-circle-fill' : 'bi-play-circle-fill' }}"></i>
                    {{ $flow->status === 'active' ? __('Pause') : __('Activate') }}
                </button>
                <a href="{{ route('flows.edit', $flow->id) }}" class="fc-btn primary">
                    <i class="bi bi-pencil"></i> {{ __('Edit') }}
                </a>
                <a href="{{ route('flows.analytics', $flow->id) }}" class="fc-btn" title="{{ __('Analytics') }}">
                    <i class="bi bi-bar-chart-fill"></i>
                </a>
            </div>
        </div>
        @empty
        <div class="flows-empty">
            <div class="flows-empty-icon">
                <i class="bi bi-diagram-3" style="font-size:2.5rem"></i>
            </div>
            <h6>{{ __('No flows yet') }}</h6>
            <p>{{ __('Build your first automated conversation. Drag nodes, connect them, and go live in minutes.') }}</p>
            <a href="{{ route('flows.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg"></i> {{ __('Create first flow') }}
            </a>
        </div>
        @endforelse
    </div>

    @if($flows->hasPages())
    <div class="mt-4">{{ $flows->links() }}</div>
    @endif

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrf     = document.querySelector('meta[name="csrf-token"]').content;
    const BASE_URL = "{{ rtrim(route('flows.index'), '/') }}";

    document.querySelectorAll('.btn-toggle-flow').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id   = this.dataset.id;
            const self = this;
            self.disabled = true;
            fetch(BASE_URL + '/' + id + '/toggle', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            }).then(r => r.json()).then(d => {
                if (!d.error) {
                    // Update button and status badge without full reload
                    const card   = self.closest('.flow-card');
                    const badge  = card.querySelector('.fc-status');
                    const isNowActive = d.status === 'active';
                    badge.className = 'fc-status ' + d.status;
                    badge.innerHTML = `<span class="dot"></span> ${isNowActive ? '{{ __('Active') }}' : '{{ __('Draft') }}'}`;
                    self.className  = 'fc-toggle ' + (isNowActive ? 'is-active' : 'is-draft') + ' btn-toggle-flow';
                    self.dataset.status = d.status;
                    self.innerHTML  = `<i class="bi ${isNowActive ? 'bi-pause-circle-fill' : 'bi-play-circle-fill'}"></i> ${isNowActive ? '{{ __('Pause') }}' : '{{ __('Activate') }}'}`;
                    if (typeof toastr !== 'undefined') toastr.success(d.message);
                } else {
                    alert(d.message);
                }
            }).finally(() => { self.disabled = false; });
        });
    });

    document.querySelectorAll('.btn-delete-flow').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!confirm('{{ __('Delete this flow? Active sessions will end.') }}')) return;
            const id   = this.dataset.id;
            const card = this.closest('.flow-card');
            fetch(BASE_URL + '/' + id, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            }).then(r => r.json()).then(d => {
                if (!d.error) {
                    card.style.transition = 'opacity 250ms, transform 250ms';
                    card.style.opacity    = '0';
                    card.style.transform  = 'scale(.97)';
                    setTimeout(() => card.remove(), 260);
                } else {
                    alert(d.message);
                }
            });
        });
    });

    document.querySelectorAll('.btn-duplicate-flow').forEach(function (btn) {
        btn.addEventListener('click', function () {
            fetch(BASE_URL + '/' + this.dataset.id + '/duplicate', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
            }).then(r => r.json()).then(d => {
                if (!d.error) window.location.href = d.redirect;
                else alert(d.message);
            });
        });
    });
});
</script>

</x-layout-dashboard>
