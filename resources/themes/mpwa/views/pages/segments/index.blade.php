<x-layout-dashboard title="{{ __('Audience Segments') }}">

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center py-2">
        <span class="fw-semibold"><i class="bi bi-funnel-fill me-1"></i>{{ __('Audience Segments') }}</span>
        <a href="{{ route('segments.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1"></i>{{ __('New Segment') }}
        </a>
    </div>

    <div class="card-body pb-2">
        <p class="text-muted small mb-3">{{ __('Segments are dynamic audiences built from contact filters. Use them instead of phonebooks when creating campaigns.') }}</p>

        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-2">
                <thead class="table-light">
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Contacts') }}</th>
                        <th>{{ __('Conditions') }}</th>
                        <th>{{ __('Last Computed') }}</th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($segments as $segment)
                    <tr>
                        <td class="fw-semibold">{{ $segment->name }}</td>
                        <td><span class="badge bg-primary">{{ number_format($segment->contact_count) }}</span></td>
                        <td class="text-muted small">
                            @php $rules = $segment->rules; @endphp
                            {{ count($rules['conditions'] ?? []) }} {{ __('condition(s)') }}
                            <span class="badge bg-light text-dark ms-1">{{ $rules['operator'] ?? 'AND' }}</span>
                        </td>
                        <td class="text-muted small">{{ $segment->last_computed_at ? $segment->last_computed_at->diffForHumans() : __('Never') }}</td>
                        <td class="text-end">
                            <a href="{{ route('campaign.create') }}?segment_id={{ $segment->id }}" class="btn btn-sm btn-outline-primary me-1">
                                <i class="bi bi-send"></i> {{ __('Campaign') }}
                            </a>
                            <button class="btn btn-sm btn-outline-danger btn-delete-segment" data-id="{{ $segment->id }}">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="bi bi-funnel d-block mb-2" style="font-size:2.5rem"></i>
                            {{ __('No segments yet.') }}
                            <a href="{{ route('segments.create') }}" class="d-block mt-2">{{ __('Create your first segment') }}</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $segments->links() }}
    </div>
</div>

<script>
document.querySelectorAll('.btn-delete-segment').forEach(function (btn) {
    btn.addEventListener('click', function () {
        if (!confirm('{{ __('Delete this segment?') }}')) return;
        fetch('/segments/' + this.dataset.id, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(d => { if (!d.error) location.reload(); else alert(d.message); });
    });
});
</script>

</x-layout-dashboard>
