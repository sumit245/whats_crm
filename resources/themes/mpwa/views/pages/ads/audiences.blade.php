<x-layout-dashboard title="{{ __('Ad Audiences') }}">
@if (session()->has('alert'))
<x-alert>
    @slot('type', session('alert')['type'])
    @slot('msg', session('alert')['msg'])
</x-alert>
@endif

<x-page-header title="{{ __('Saved Audiences') }}"
    subtitle="{{ __('Reusable targeting definitions for your ad campaigns') }}"
    :breadcrumb="[__('Ads Manager'), __('Audiences')]" />

<div class="d-flex justify-content-end mb-3">
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAudienceModal">
        <i class="bi bi-plus-lg me-1"></i>{{ __('New Audience') }}
    </button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">{{ __('Audience') }}</th>
                        <th>{{ __('Channel') }}</th>
                        <th>{{ __('Est. Size') }}</th>
                        <th>{{ __('Definition') }}</th>
                        <th>{{ __('External ID') }}</th>
                        <th class="pe-3">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @if($audiences->isEmpty())
                    <x-no-data colspan="6" text="{{ __('No saved audiences yet') }}" />
                @else
                    @foreach($audiences as $aud)
                    <tr>
                        <td class="ps-3 fw-semibold small">{{ $aud->name }}</td>
                        <td>
                            @if($aud->channel)
                            <i class="bi {{ $aud->channel->typeIcon() }} text-{{ $aud->channel->typeColor() }} me-1"></i>
                            <span class="small">{{ $aud->channel->typeLabel() }}</span>
                            @else
                            <span class="text-muted small">{{ __('Generic') }}</span>
                            @endif
                        </td>
                        <td class="small">{{ $aud->estimatedSizeLabel() }}</td>
                        <td class="small text-muted">
                            @php $def = $aud->definition; @endphp
                            Age {{ $def['age_min'] ?? 18 }}–{{ $def['age_max'] ?? 65 }}
                            @if(!empty($def['locations'])) • {{ implode(', ', (array)$def['locations']) }} @endif
                        </td>
                        <td class="small font-monospace text-muted">{{ $aud->external_audience_id ?? '—' }}</td>
                        <td class="pe-3">
                            <button class="btn btn-sm btn-outline-info me-1" onclick="syncAudience({{ $aud->id }})" title="{{ __('Sync size') }}">
                                <i class="bi bi-arrow-repeat"></i>
                            </button>
                            <form method="POST" action="{{ route('ads.audiences.destroy', $aud) }}" class="d-inline"
                                  onsubmit="return confirm('{{ __('Delete audience?') }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                @endif
                </tbody>
            </table>
        </div>
    </div>
    @if($audiences->hasPages())
    <div class="card-footer bg-transparent">{{ $audiences->links() }}</div>
    @endif
</div>

{{-- Add Audience Modal --}}
<div class="modal fade" id="addAudienceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('ads.audiences.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('New Saved Audience') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('Audience Name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('Channel') }}</label>
                        <select name="ad_channel_id" class="form-select">
                            <option value="">{{ __('Generic (all channels)') }}</option>
                            @foreach($channels as $ch)
                            <option value="{{ $ch->id }}">{{ $ch->name }} ({{ $ch->typeLabel() }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">{{ __('Min Age') }}</label>
                            <input type="number" name="definition[age_min]" class="form-control" value="18" min="13" max="65">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">{{ __('Max Age') }}</label>
                            <input type="number" name="definition[age_max]" class="form-control" value="65" min="13" max="65">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('Countries') }} <span class="text-muted small">(ISO codes)</span></label>
                        <input type="text" name="definition[locations]" class="form-control" placeholder="IN, US, AE">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('Interests') }} <span class="text-muted small">(comma-separated IDs)</span></label>
                        <input type="text" name="definition[interests]" class="form-control" placeholder="{{ __('Meta interest IDs') }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save Audience') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

</x-layout-dashboard>

<script>
function syncAudience(id) {
    $.ajax({
        url: '{{ url("") }}/' + '{{ app()->getLocale() }}' + '/ads/audiences/' + id + '/sync',
        type: 'POST',
        data: { _token: '{{ csrf_token() }}' },
        success: res => res.error ? toastr.error(res.msg) : toastr.success(res.msg),
        error: () => toastr.error('{{ __("Something went wrong") }}'),
    });
}
</script>
