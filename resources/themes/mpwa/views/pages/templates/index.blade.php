<x-layout-dashboard title="{{ __('Template Library') }}">

    @if (session()->has('alert'))
        <x-alert>
            @slot('type', session('alert')['type'])
            @slot('msg', session('alert')['msg'])
        </x-alert>
    @endif

    <x-page-header title="{{ __('HSM Template Library') }}"
        subtitle="{{ __('Manage Meta-approved WhatsApp Business message templates') }}"
        :breadcrumb="[__('Templates')]">
        <div class="d-flex gap-2 align-items-center">
            <select id="syncDeviceSelect" class="form-select form-select-sm" style="min-width:180px">
                <option value="">{{ __('All devices') }}</option>
                @foreach ($devices as $device)
                    <option value="{{ $device->id }}">{{ $device->meta_profile['verified_name'] ?? $device->body }}</option>
                @endforeach
            </select>
            <button type="button" id="syncBtn" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-arrow-repeat"></i> {{ __('Sync All from Meta') }}
            </button>
        </div>
        <a href="{{ route('templates.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus"></i> {{ __('Create Template') }}
        </a>
    </x-page-header>

            {{-- Filters --}}
            <div class="card mb-3">
                <div class="card-body py-2">
                    <form method="GET" class="d-flex gap-3 align-items-center flex-wrap">
                        <select name="status" class="form-select form-select-sm" style="width:auto">
                            <option value="">{{ __('All Statuses') }}</option>
                            @foreach (['APPROVED','PENDING','REJECTED','PAUSED','DISABLED'] as $s)
                                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                        <select name="category" class="form-select form-select-sm" style="width:auto">
                            <option value="">{{ __('All Categories') }}</option>
                            @foreach (['MARKETING','UTILITY','AUTHENTICATION'] as $c)
                                <option value="{{ $c }}" {{ request('category') === $c ? 'selected' : '' }}>{{ $c }}</option>
                            @endforeach
                        </select>
                        <select name="device_id" class="form-select form-select-sm" style="width:auto">
                            <option value="">{{ __('All Devices') }}</option>
                            @foreach ($devices as $d)
                                <option value="{{ $d->id }}" {{ request('device_id') == $d->id ? 'selected' : '' }}>
                                    {{ $d->meta_profile['verified_name'] ?? $d->body }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-sm btn-secondary">{{ __('Filter') }}</button>
                        <a href="{{ route('templates.index') }}" class="btn btn-sm btn-link">{{ __('Clear') }}</a>
                    </form>
                </div>
            </div>

            {{-- Templates table --}}
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('Template Name') }}</th>
                                    <th>{{ __('Category') }}</th>
                                    <th>{{ __('Language') }}</th>
                                    <th>{{ __('Device') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Last Synced') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($templates as $template)
                                    <tr>
                                        <td>
                                            <strong class="font-monospace">{{ $template->name }}</strong>
                                            @if ($template->rejection_reason)
                                                <br><small class="text-danger"><i class="bi bi-exclamation-circle"></i> {{ Str::limit($template->rejection_reason, 60) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $template->category_color }}">{{ $template->category }}</span>
                                        </td>
                                        <td><span class="badge bg-light text-dark">{{ strtoupper($template->language) }}</span></td>
                                        <td>
                                            <small>{{ $template->device->meta_profile['verified_name'] ?? $template->device->body ?? '—' }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $template->status_color }}">{{ $template->status }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $template->meta_synced_at?->diffForHumans() ?? __('Never') }}</small>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1 align-items-center">
                                                {{-- Per-template instant refresh --}}
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-primary refresh-btn"
                                                    data-id="{{ $template->id }}"
                                                    data-status="{{ $template->status }}"
                                                    title="{{ __('Refresh status from Meta') }}">
                                                    <i class="bi bi-arrow-clockwise"></i>
                                                </button>
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-secondary preview-btn"
                                                    data-id="{{ $template->id }}"
                                                    data-bs-toggle="modal" data-bs-target="#previewModal"
                                                    title="{{ __('Preview') }}">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-danger delete-btn"
                                                    data-id="{{ $template->id }}"
                                                    data-name="{{ $template->name }}"
                                                    title="{{ __('Delete') }}">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-5">
                                            <i class="bi bi-layout-text-sidebar-reverse fs-1 d-block mb-2"></i>
                                            {{ __('No templates yet. Create one or sync from Meta.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($templates->hasPages())
                    <div class="card-footer">{{ $templates->links() }}</div>
                @endif
            </div>

{{-- Preview Modal --}}
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Template Preview') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="previewContent">
                <div class="text-center py-4"><div class="spinner-border"></div></div>
            </div>
        </div>
    </div>
</div>

<script>
const CSRF = $('meta[name="csrf-token"]').attr('content');

// ── Sync all (or one device) from Meta ───────────────────────────
$('#syncBtn').on('click', function () {
    const deviceId = $('#syncDeviceSelect').val();
    const btn = $(this);
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> {{ __("Syncing...") }}');
    $.ajax({
        method: 'POST',
        url: '{{ route("templates.sync") }}',
        headers: { 'X-CSRF-TOKEN': CSRF },
        data: deviceId ? { device_id: deviceId } : {},
        success: (res) => {
            toastr.success(res.message);
            setTimeout(() => location.reload(), 1200);
        },
        error: (err) => {
            toastr.error(err.responseJSON?.message ?? 'Sync failed');
            btn.prop('disabled', false).html('<i class="bi bi-arrow-repeat"></i> {{ __("Sync All from Meta") }}');
        },
    });
});

// ── Per-row instant status refresh ───────────────────────────────
$(document).on('click', '.refresh-btn', function () {
    const btn      = $(this);
    const id       = btn.data('id');
    const row      = btn.closest('tr');
    const statusEl = row.find('.badge[class*="bg-"]').filter(':not(.bg-primary):not(.bg-info):not(.bg-warning):not(.bg-light)').first();

    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

    $.ajax({
        method: 'POST',
        url: `/templates/${id}/refresh`,
        headers: { 'X-CSRF-TOKEN': CSRF },
        success: (res) => {
            if (res.changed) {
                // Update the status badge in-place without a full reload
                const colorMap = {
                    APPROVED: 'bg-success', PENDING: 'bg-warning',
                    REJECTED: 'bg-danger',  PAUSED: 'bg-secondary',
                    DISABLED: 'bg-dark',
                };
                const badgeClass = colorMap[res.new_status] || 'bg-secondary';
                // Find the status badge (5th column)
                const statusBadge = row.find('td:eq(4) .badge');
                statusBadge.removeClass('bg-success bg-warning bg-danger bg-secondary bg-dark')
                           .addClass(badgeClass)
                           .text(res.new_status);
                // Update last synced column
                row.find('td:eq(5) small').text('just now');

                toastr.success(res.message);
            } else {
                toastr.info(res.message);
            }
        },
        error: (err) => {
            toastr.error(err.responseJSON?.message ?? '{{ __("Refresh failed. Check device credentials.") }}');
        },
        complete: () => {
            btn.prop('disabled', false).html('<i class="bi bi-arrow-clockwise"></i>');
        },
    });
});

// Preview template
$(document).on('click', '.preview-btn', function () {
    const id = $(this).data('id');
    $('#previewContent').html('<div class="text-center py-4"><div class="spinner-border"></div></div>');
    $.get('{{ url("templates") }}/' + id, function (data) {
        let html = `<div class="font-monospace small"><strong>${data.name}</strong> &nbsp;
            <span class="badge bg-primary">${data.category}</span>
            <span class="badge bg-${data.status === 'APPROVED' ? 'success' : data.status === 'REJECTED' ? 'danger' : 'warning'}">${data.status}</span></div>`;
        html += '<hr>';
        (data.components || []).forEach(c => {
            html += `<div class="mb-2"><span class="badge bg-secondary">${c.type}</span> `;
            if (c.text) html += `<span>${c.text}</span>`;
            if (c.format) html += ` <em class="text-muted">(${c.format})</em>`;
            if (c.buttons) {
                c.buttons.forEach(b => {
                    html += `<div class="ms-3"><i class="bi bi-arrow-return-right"></i> [${b.type}] ${b.text || ''} ${b.url || b.phone_number || ''}</div>`;
                });
            }
            html += '</div>';
        });
        $('#previewContent').html(html);
    });
});

// Delete template
$(document).on('click', '.delete-btn', function () {
    const id = $(this).data('id');
    const name = $(this).data('name');
    if (!confirm(`{{ __("Delete template") }} "${name}"? {{ __("This cannot be undone.") }}`)) return;
    $.ajax({
        method: 'DELETE',
        url: '{{ url("templates") }}/' + id,
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: (res) => {
            toastr.success(res.message);
            setTimeout(() => location.reload(), 800);
        },
        error: (err) => toastr.error(err.responseJSON?.message ?? 'Delete failed'),
    });
});
</script>

</x-layout-dashboard>
