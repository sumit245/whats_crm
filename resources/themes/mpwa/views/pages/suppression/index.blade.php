<x-layout-dashboard title="{{ __('Suppression List') }}">

<div class="page-content">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="material-icons-two-tone text-danger me-1">block</i> {{ __('Suppression List') }}</h5>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="material-icons">add</i> {{ __('Add Number') }}
                    </button>
                </div>

                <div class="card-body">
                    <p class="text-muted mb-3">
                        {{ __('Numbers on this list are automatically skipped during campaign blasts. Numbers are added automatically when Meta reports permanent delivery failures, or you can add them manually.') }}
                    </p>

                    {{-- Search --}}
                    <form method="GET" class="mb-3 d-flex gap-2">
                        <input type="text" name="search" class="form-control w-auto" placeholder="{{ __('Search number...') }}" value="{{ request('search') }}">
                        <button class="btn btn-outline-secondary">{{ __('Search') }}</button>
                        @if(request('search'))
                            <a href="{{ route('suppression.index') }}" class="btn btn-outline-danger">{{ __('Clear') }}</a>
                        @endif
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('Number') }}</th>
                                    <th>{{ __('Reason') }}</th>
                                    <th>{{ __('Note') }}</th>
                                    <th>{{ __('Added') }}</th>
                                    <th class="text-end">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($entries as $entry)
                                <tr>
                                    <td><code>{{ $entry->number }}</code></td>
                                    <td>
                                        @php
                                            $badge = match($entry->reason) {
                                                'meta_block'   => ['bg-danger', __('Meta Block')],
                                                'user_optout'  => ['bg-warning text-dark', __('Opt-Out')],
                                                default        => ['bg-secondary', __('Manual')],
                                            };
                                        @endphp
                                        <span class="badge {{ $badge[0] }}">{{ $badge[1] }}</span>
                                    </td>
                                    <td class="text-muted small">{{ $entry->note ?? '—' }}</td>
                                    <td class="text-muted small">{{ $entry->created_at->diffForHumans() }}</td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-danger btn-remove"
                                                data-id="{{ $entry->id }}"
                                                data-url="{{ route('suppression.destroy', $entry->id) }}">
                                            <i class="material-icons" style="font-size:16px;">delete</i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="material-icons d-block mb-2" style="font-size:40px;">check_circle</i>
                                        {{ __('No suppressed numbers. Your list is clean.') }}
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $entries->withQueryString()->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add Number Modal --}}
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="addForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Suppress a Number') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('Phone Number') }} <span class="text-danger">*</span></label>
                        <input type="text" name="number" id="sup-number" class="form-control" placeholder="628123456789" required>
                        <div class="form-text">{{ __('Include country code without + (e.g. 628123456789)') }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Note (optional)') }}</label>
                        <input type="text" name="note" class="form-control" placeholder="{{ __('Reason or reference...') }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-danger">{{ __('Suppress Number') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Add number
    document.getElementById('addForm').addEventListener('submit', function (e) {
        e.preventDefault();
        fetch('{{ route('suppression.store') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ number: document.getElementById('sup-number').value, note: this.note?.value })
        })
        .then(r => r.json())
        .then(d => {
            if (!d.error) { location.reload(); }
            else { alert(d.message); }
        });
    });

    // Remove number
    document.querySelectorAll('.btn-remove').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!confirm('{{ __('Remove this number from suppression list?') }}')) return;
            fetch(this.dataset.url, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(d => { if (!d.error) location.reload(); else alert(d.message); });
        });
    });
});
</script>

</x-layout-dashboard>
