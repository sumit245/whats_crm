<x-layout-dashboard title="{{ __('Ad Campaigns') }}">
@if (session()->has('alert'))
<x-alert>
    @slot('type', session('alert')['type'])
    @slot('msg', session('alert')['msg'])
</x-alert>
@endif

<x-page-header title="{{ __('Ad Campaigns') }}"
    subtitle="{{ __('Manage and monitor your multi-channel ad campaigns') }}"
    :breadcrumb="[__('Ads Manager'), __('Campaigns')]" />

<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('ads.campaigns.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i>{{ __('New Campaign') }}
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">{{ __('Campaign') }}</th>
                        <th>{{ __('Objective') }}</th>
                        <th>{{ __('Channels') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Budget/day') }}</th>
                        <th>{{ __('Impressions') }}</th>
                        <th>{{ __('Clicks') }}</th>
                        <th>{{ __('Spend') }}</th>
                        <th class="pe-3">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @if($campaigns->isEmpty())
                    <x-no-data colspan="9" text="{{ __('No campaigns yet') }}" />
                @else
                    @foreach($campaigns as $camp)
                    @php
                        $totalImpr   = $camp->placements->sum(fn($p) => $p->cachedImpressions());
                        $totalClicks = $camp->placements->sum(fn($p) => $p->cachedClicks());
                        $totalSpend  = $camp->placements->sum(fn($p) => $p->cachedSpend());
                    @endphp
                    <tr>
                        <td class="ps-3">
                            <div class="fw-semibold small">{{ $camp->name }}</div>
                            <div class="text-muted" style="font-size:11px">{{ $camp->created_at->format('d M Y') }}</div>
                        </td>
                        <td class="small">{{ $camp->objectiveLabel() }}</td>
                        <td>
                            <div class="d-flex gap-1">
                                @foreach($camp->placements->unique('ad_channel_id') as $pl)
                                    @if($pl->channel)
                                    <i class="bi {{ $pl->channel->typeIcon() }} text-{{ $pl->channel->typeColor() }} fs-6" title="{{ $pl->channel->typeLabel() }}"></i>
                                    @endif
                                @endforeach
                                @if($camp->placements_count === 0)
                                <span class="text-muted small">—</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-{{ $camp->statusColor() }} bg-opacity-15 text-{{ $camp->statusColor() }} border" style="border-color:currentColor!important">
                                {{ ucfirst($camp->status) }}
                            </span>
                        </td>
                        <td class="small">{{ $camp->budget_daily ? '$' . number_format($camp->budget_daily, 2) : '—' }}</td>
                        <td class="small">{{ number_format($totalImpr) }}</td>
                        <td class="small">{{ number_format($totalClicks) }}</td>
                        <td class="small">${{ number_format($totalSpend, 2) }}</td>
                        <td class="pe-3">
                            <div class="d-flex gap-1">
                                <a href="{{ route('ads.campaigns.show', $camp) }}" class="btn btn-sm btn-outline-primary" title="{{ __('View') }}">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if($camp->status === 'draft')
                                <button class="btn btn-sm btn-success" onclick="launchCampaign({{ $camp->id }})" title="{{ __('Launch') }}">
                                    <i class="bi bi-play-fill"></i>
                                </button>
                                @elseif($camp->status === 'active')
                                <button class="btn btn-sm btn-warning" onclick="pauseCampaign({{ $camp->id }})" title="{{ __('Pause') }}">
                                    <i class="bi bi-pause-fill"></i>
                                </button>
                                @endif
                                <form method="POST" action="{{ route('ads.campaigns.destroy', $camp) }}" class="d-inline"
                                      onsubmit="return confirm('{{ __('Delete this campaign?') }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Delete') }}">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                @endif
                </tbody>
            </table>
        </div>
    </div>
    @if($campaigns->hasPages())
    <div class="card-footer bg-transparent">
        {{ $campaigns->links() }}
    </div>
    @endif
</div>

</x-layout-dashboard>

<script>
function launchCampaign(id) {
    if (!confirm('{{ __("Launch this campaign on all selected channels?") }}')) return;
    $.ajax({
        url: '{{ url("") }}/' + '{{ app()->getLocale() }}' + '/ads/campaigns/' + id + '/launch',
        type: 'POST',
        data: { _token: '{{ csrf_token() }}' },
        success: res => {
            res.error ? toastr.error(res.msg) : toastr.success(res.msg);
            if (!res.error) setTimeout(() => location.reload(), 1500);
        },
        error: () => toastr.error('{{ __("Something went wrong") }}'),
    });
}

function pauseCampaign(id) {
    $.ajax({
        url: '{{ url("") }}/' + '{{ app()->getLocale() }}' + '/ads/campaigns/' + id + '/pause',
        type: 'POST',
        data: { _token: '{{ csrf_token() }}' },
        success: res => {
            res.error ? toastr.error(res.msg) : toastr.success(res.msg);
            if (!res.error) setTimeout(() => location.reload(), 1200);
        },
        error: () => toastr.error('{{ __("Something went wrong") }}'),
    });
}
</script>
