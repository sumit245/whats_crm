<x-layout-dashboard title="{{ __('API Health') }}">

    <x-page-header title="{{ __('API Health Monitor') }}"
        subtitle="{{ __('Live phone number quality, messaging tier, and template status from Meta.') }}"
        :breadcrumb="[__('API Health')]" />

            @if ($devices->isEmpty())
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    {{ __('No connected devices found.') }}
                    <a href="{{ route('home') }}" class="alert-link">{{ __('Add a device') }}</a>
                </div>
            @endif

            <div class="row g-3" id="healthCards">
                @foreach ($devices as $device)
                    @php $h = $healthData[$device->id] ?? []; @endphp
                    <div class="col-md-6 col-xl-4">
                        <div class="card border-0 shadow-sm h-100" id="healthCard_{{ $device->id }}">
                            <div class="card-header bg-transparent d-flex align-items-center">
                                <div>
                                    <strong>{{ $h['verified_name'] ?? $device->body }}</strong>
                                    <br><small class="text-muted font-monospace">{{ $h['display_phone'] ?? '' }}</small>
                                </div>
                                <button class="btn btn-sm btn-outline-secondary ms-auto refresh-health-btn"
                                    data-device="{{ $device->id }}">
                                    <i class="bi bi-arrow-repeat"></i>
                                </button>
                            </div>
                            <div class="card-body">

                                @if (isset($h['error']) && $h['error'])
                                    <div class="alert alert-danger py-2 small">
                                        <i class="bi bi-exclamation-circle me-1"></i>{{ $h['error'] }}
                                    </div>
                                @endif

                                {{-- Quality Rating --}}
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="text-muted">{{ __('Quality Rating') }}</span>
                                    @php
                                        $qColor = match($h['quality_rating'] ?? '') { 'GREEN' => 'success', 'YELLOW' => 'warning', 'RED' => 'danger', default => 'secondary' };
                                    @endphp
                                    <span class="badge bg-{{ $qColor }} fs-6">
                                        <i class="bi bi-circle-fill me-1" style="font-size:0.5rem"></i>
                                        {{ $h['quality_rating'] ?? 'N/A' }}
                                    </span>
                                </div>

                                {{-- Messaging Tier --}}
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="text-muted">{{ __('Messaging Tier') }}</span>
                                    <span class="badge bg-info text-dark">
                                        {{ str_replace('TIER_', '', $h['messaging_tier'] ?? 'UNKNOWN') }}
                                        <small>/ day</small>
                                    </span>
                                </div>

                                {{-- Daily limit bar --}}
                                @php
                                    $limit = $h['conversations_limit'] ?? 1000;
                                    $used  = $h['conversations_used']  ?? 0;
                                    $pct   = $limit > 0 ? min(100, round($used / $limit * 100)) : 0;
                                @endphp
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <small class="text-muted">{{ __('Daily Conversation Limit') }}</small>
                                        <small>{{ number_format($used) }} / {{ number_format($limit) }}</small>
                                    </div>
                                    <div class="progress" style="height:8px">
                                        <div class="progress-bar bg-{{ $pct > 80 ? 'danger' : ($pct > 50 ? 'warning' : 'success') }}"
                                            style="width:{{ $pct }}%"></div>
                                    </div>
                                </div>

                                {{-- Template stats --}}
                                <div class="border-top pt-3">
                                    <small class="text-muted d-block mb-2">{{ __('Templates') }}</small>
                                    <div class="d-flex gap-2">
                                        <span class="badge bg-success">
                                            <i class="bi bi-check-circle me-1"></i>
                                            {{ $h['template_stats']['APPROVED'] ?? 0 }} {{ __('Approved') }}
                                        </span>
                                        <span class="badge bg-warning text-dark">
                                            {{ $h['template_stats']['PENDING'] ?? 0 }} {{ __('Pending') }}
                                        </span>
                                        <span class="badge bg-danger">
                                            {{ $h['template_stats']['REJECTED'] ?? 0 }} {{ __('Rejected') }}
                                        </span>
                                    </div>
                                </div>

                            </div>
                            <div class="card-footer bg-transparent text-muted small">
                                <i class="bi bi-clock me-1"></i>{{ __('Cached for 5 minutes. Click refresh for live data.') }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

<script>
$(document).on('click', '.refresh-health-btn', function () {
    const deviceId = $(this).data('device');
    const btn = $(this);
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

    $.ajax({
        method: 'POST',
        url: '{{ url("meta/health/refresh") }}/' + deviceId,
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: (res) => {
            toastr.success('{{ __("Health data refreshed") }}');
            setTimeout(() => location.reload(), 500);
        },
        error: () => {
            toastr.error('{{ __("Refresh failed") }}');
            btn.prop('disabled', false).html('<i class="bi bi-arrow-repeat"></i>');
        },
    });
});
</script>

</x-layout-dashboard>
