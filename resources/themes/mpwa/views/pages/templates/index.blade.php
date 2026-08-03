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
        <a href="{{ route('templates.library') }}" class="btn btn-outline-success btn-sm">
            <i class="bi bi-collection"></i> {{ __('Browse Library') }}
        </a>
        <a href="{{ route('templates.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> {{ __('Create Template') }}
        </a>
    </x-page-header>

    {{-- Sync + filters --}}
    <div class="card templates-toolbar mb-3">
        <div class="card-body py-3">
            <div class="templates-toolbar__grid">
                <div class="templates-toolbar__group">
                    <span class="templates-toolbar__label">{{ __('Sync from Meta') }}</span>
                    <select id="syncDeviceSelect" class="form-select form-select-sm">
                        <option value="">{{ __('All devices') }}</option>
                        @foreach ($devices as $device)
                            <option value="{{ $device->id }}">{{ $device->meta_profile['verified_name'] ?? $device->body }}</option>
                        @endforeach
                    </select>
                    <button type="button" id="syncBtn" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-arrow-repeat"></i> {{ __('Sync') }}
                    </button>
                </div>
                <div class="templates-toolbar__divider d-none d-md-block" aria-hidden="true"></div>
                <form method="GET" class="templates-toolbar__group templates-toolbar__group--filters">
                    <span class="templates-toolbar__label">{{ __('Filter') }}</span>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">{{ __('All Statuses') }}</option>
                        @foreach (['APPROVED', 'PENDING', 'REJECTED', 'PAUSED', 'DISABLED'] as $s)
                            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                    <select name="category" class="form-select form-select-sm">
                        <option value="">{{ __('All Categories') }}</option>
                        @foreach (['MARKETING', 'UTILITY', 'AUTHENTICATION'] as $c)
                            <option value="{{ $c }}" {{ request('category') === $c ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                    <select name="device_id" class="form-select form-select-sm">
                        <option value="">{{ __('All Devices') }}</option>
                        @foreach ($devices as $d)
                            <option value="{{ $d->id }}" {{ request('device_id') == $d->id ? 'selected' : '' }}>
                                {{ $d->meta_profile['verified_name'] ?? $d->body }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-secondary">
                        <i class="bi bi-funnel"></i> {{ __('Apply') }}
                    </button>
                    @if (request()->hasAny(['status', 'category', 'device_id']))
                        <a href="{{ route('templates.index') }}" class="btn btn-sm btn-link">{{ __('Clear') }}</a>
                    @endif
                </form>
            </div>
        </div>
    </div>

    {{-- Templates table --}}
    <div class="card templates-list-card mb-0">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <span class="fw-semibold mb-0">{{ __('Templates') }}</span>
            <span class="badge text-bg-secondary">{{ $templates->total() }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Template Name') }}</th>
                            <th>{{ __('Category') }}</th>
                            <th>{{ __('Language') }}</th>
                            <th>{{ __('Device') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Last Synced') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($templates as $template)
                            @php
                                $rejection = $template->rejection_reason;
                                $showRejection = $rejection
                                    && ! in_array(strtoupper(trim($rejection)), ['', 'NONE', 'N/A'], true);
                            @endphp
                            <tr>
                                <td>
                                    <strong class="font-monospace">{{ $template->name }}</strong>
                                    @if ($showRejection)
                                        <br><small class="text-danger"><i class="bi bi-exclamation-circle"></i> {{ Str::limit($rejection, 60) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $template->category_color }}">{{ $template->category }}</span>
                                </td>
                                <td><span class="badge text-bg-secondary">{{ strtoupper($template->language) }}</span></td>
                                <td>
                                    <small class="text-muted">{{ $template->device->meta_profile['verified_name'] ?? $template->device->body ?? '—' }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $template->status_color }}">{{ $template->status }}</span>
                                </td>
                                <td>
                                    <small class="text-muted">{{ $template->meta_synced_at?->diffForHumans() ?? __('Never') }}</small>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1 template-actions">
                                        <button type="button"
                                            class="btn btn-sm btn-outline-primary refresh-btn"
                                            data-id="{{ $template->id }}"
                                            title="{{ __('Refresh status from Meta') }}">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </button>
                                        <button type="button"
                                            class="btn btn-sm btn-outline-secondary preview-btn"
                                            data-id="{{ $template->id }}"
                                            data-bs-toggle="modal"
                                            data-bs-target="#previewModal"
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
                                <td colspan="7">
                                    <div class="mpwa-empty">
                                        <i class="bi bi-layout-text-sidebar-reverse"></i>
                                        <p class="mb-2">{{ __('No templates yet.') }}</p>
                                        <a href="{{ route('templates.create') }}" class="btn btn-sm btn-primary">{{ __('Create Template') }}</a>
                                        <span class="mx-1 text-muted">{{ __('or') }}</span>
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="syncBtnEmpty">
                                            <i class="bi bi-arrow-repeat"></i> {{ __('Sync from Meta') }}
                                        </button>
                                    </div>
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
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Template Preview') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body" id="previewContent">
                    <div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>
                </div>
            </div>
        </div>
    </div>

    <script>
    const CSRF = $('meta[name="csrf-token"]').attr('content');
    const API_BASE = (window.BASE_URL || '').replace(/\/$/, '');
    const syncBtnLabel = '<i class="bi bi-arrow-repeat"></i> {{ __("Sync") }}';
    const syncBtnLoading = '<span class="spinner-border spinner-border-sm"></span> {{ __("Syncing...") }}';

    function runTemplateSync(btn) {
        const deviceId = $('#syncDeviceSelect').val();
        btn.prop('disabled', true).html(syncBtnLoading);
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
                btn.prop('disabled', false).html(syncBtnLabel);
            },
        });
    }

    $('#syncBtn, #syncBtnEmpty').on('click', function () {
        runTemplateSync($(this));
    });

    $(document).on('click', '.refresh-btn', function () {
        const btn = $(this);
        const id = btn.data('id');
        const row = btn.closest('tr');

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            method: 'POST',
            url: `${API_BASE}/templates/${id}/refresh`,
            headers: { 'X-CSRF-TOKEN': CSRF },
            success: (res) => {
                if (res.changed) {
                    const colorMap = {
                        APPROVED: 'bg-success', PENDING: 'bg-warning',
                        REJECTED: 'bg-danger', PAUSED: 'bg-secondary',
                        DISABLED: 'bg-dark',
                    };
                    const badgeClass = colorMap[res.new_status] || 'bg-secondary';
                    const statusBadge = row.find('td:eq(4) .badge');
                    statusBadge.removeClass('bg-success bg-warning bg-danger bg-secondary bg-dark')
                        .addClass(badgeClass)
                        .text(res.new_status);
                    row.find('td:eq(5) small').text('{{ __("just now") }}');
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

    $(document).on('click', '.preview-btn', function () {
        const id = $(this).data('id');
        $('#previewContent').html('<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>');
        $.get(`${API_BASE}/templates/${id}`, function (data) {
            const statusClass = data.status === 'APPROVED' ? 'success' : (data.status === 'REJECTED' ? 'danger' : 'warning');
            let html = `<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <strong class="font-monospace">${data.name}</strong>
                <span class="badge bg-primary">${data.category}</span>
                <span class="badge bg-${statusClass}">${data.status}</span>
            </div>`;
            (data.components || []).forEach(c => {
                html += `<div class="border rounded p-2 mb-2 mpwa-preview-panel">`;
                html += `<span class="badge text-bg-secondary me-1">${c.type}</span>`;
                if (c.text) html += `<span>${c.text}</span>`;
                if (c.format) html += ` <em class="text-muted">(${c.format})</em>`;
                if (c.buttons) {
                    c.buttons.forEach(b => {
                        html += `<div class="ms-3 mt-1 small"><i class="bi bi-arrow-return-right"></i> [${b.type}] ${b.text || ''} ${b.url || b.phone_number || ''}</div>`;
                    });
                }
                html += '</div>';
            });
            if (!(data.components || []).length) {
                html += `<p class="text-muted mb-0">{{ __('No component details stored.') }}</p>`;
            }
            $('#previewContent').html(html);
        }).fail(() => {
            $('#previewContent').html('<p class="text-danger mb-0">{{ __("Could not load template preview.") }}</p>');
        });
    });

    $(document).on('click', '.delete-btn', function () {
        const id = $(this).data('id');
        const name = $(this).data('name');
        if (!confirm(`{{ __("Delete template") }} "${name}"? {{ __("This cannot be undone.") }}`)) return;
        $.ajax({
            method: 'DELETE',
            url: `${API_BASE}/templates/${id}`,
            headers: { 'X-CSRF-TOKEN': CSRF },
            success: (res) => {
                toastr.success(res.message);
                setTimeout(() => location.reload(), 800);
            },
            error: (err) => toastr.error(err.responseJSON?.message ?? 'Delete failed'),
        });
    });
    </script>

{{-- Library is now a standalone page at /templates/library --}}
</x-layout-dashboard>
