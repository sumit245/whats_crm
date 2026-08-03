<x-layout-dashboard title="{{ __('Suppression List') }}">

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center py-2">
        <span class="fw-semibold"><i class="bi bi-slash-circle-fill text-danger me-1"></i>{{ __('Suppression List') }}</span>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="bi bi-upload me-1"></i>{{ __('Import CSV') }}
            </button>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus-lg me-1"></i>{{ __('Add Number') }}
            </button>
        </div>
    </div>

    <div class="card-body pb-2">
        <p class="text-muted small mb-3">{{ __('Numbers here are skipped during campaign blasts. Added automatically on Meta delivery failures or opt-out keywords, or manually.') }}</p>

        <form method="GET" class="d-flex gap-2 mb-3">
            <input type="text" name="search" class="form-control form-control-sm w-auto" placeholder="{{ __('Search number...') }}" value="{{ request('search') }}">
            <button class="btn btn-sm btn-outline-secondary">{{ __('Search') }}</button>
            @if(request('search'))
                <a href="{{ route('suppression.index') }}" class="btn btn-sm btn-outline-danger">{{ __('Clear') }}</a>
            @endif
        </form>

        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-2">
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
                                    'meta_block'  => ['bg-danger',           __('Meta Block')],
                                    'user_optout' => ['bg-warning text-dark', __('Opt-Out')],
                                    default       => ['bg-secondary',         __('Manual')],
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
                                <i class="bi bi-trash3"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="bi bi-check-circle d-block mb-2" style="font-size:2.5rem"></i>
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

{{-- Import CSV Modal --}}
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="importForm" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title fw-semibold">{{ __('Import Numbers from CSV') }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">{{ __('One phone number per row (first column). Include country code without + (e.g. 628123456789). Max 2 MB.') }}</p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">{{ __('CSV File') }} <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" id="csv_file" class="form-control form-control-sm" accept=".csv,.txt" required>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-sm btn-primary" id="importBtn">
                        <span class="spinner-border spinner-border-sm d-none me-1" id="importSpinner"></span>
                        {{ __('Import') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Add Number Modal --}}
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="addForm">
            @csrf
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title fw-semibold">{{ __('Suppress a Number') }}</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">{{ __('Phone Number') }} <span class="text-danger">*</span></label>
                        <input type="text" name="number" id="sup-number" class="form-control form-control-sm" placeholder="628123456789" required>
                        <div class="form-text">{{ __('Include country code without + (e.g. 628123456789)') }}</div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-semibold">{{ __('Note (optional)') }}</label>
                        <input type="text" name="note" class="form-control form-control-sm" placeholder="{{ __('Reason or reference...') }}">
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-sm btn-danger">{{ __('Suppress Number') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('importForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('importBtn');
        const spinner = document.getElementById('importSpinner');
        btn.disabled = true;
        spinner.classList.remove('d-none');
        fetch('{{ route('suppression.import') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: new FormData(this),
        })
        .then(r => r.json())
        .then(d => {
            if (d.error) { toastr.error(d.message); } else { toastr.success(d.message); setTimeout(() => location.reload(), 1500); }
        })
        .catch(() => toastr.error('{{ __('Upload failed. Please try again.') }}'))
        .finally(() => { btn.disabled = false; spinner.classList.add('d-none'); });
    });

    document.getElementById('addForm').addEventListener('submit', function (e) {
        e.preventDefault();
        fetch('{{ route('suppression.store') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ number: document.getElementById('sup-number').value, note: this.note?.value }),
        })
        .then(r => r.json())
        .then(d => { if (!d.error) location.reload(); else toastr.error(d.message); });
    });

    document.querySelectorAll('.btn-remove').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!confirm('{{ __('Remove this number from suppression list?') }}')) return;
            fetch(this.dataset.url, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            })
            .then(r => r.json())
            .then(d => { if (!d.error) location.reload(); else toastr.error(d.message); });
        });
    });
});
</script>

</x-layout-dashboard>
