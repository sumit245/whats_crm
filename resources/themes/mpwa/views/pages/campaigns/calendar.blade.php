<x-layout-dashboard title="{{ __('Campaign Calendar') }}">

{{-- FullCalendar v6 --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css">

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="mb-0 fw-semibold"><i class="bi bi-calendar3 me-2 text-primary"></i>{{ __('Campaign Calendar') }}</h5>
        <p class="text-muted small mb-0">{{ __('All scheduled campaigns. Drag to reschedule waiting campaigns.') }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('campaigns') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-list-ul me-1"></i>{{ __('List View') }}
        </a>
        <a href="{{ route('campaign.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1"></i>{{ __('New Campaign') }}
        </a>
    </div>
</div>

{{-- Legend --}}
<div class="d-flex flex-wrap gap-3 mb-3" style="font-size:0.8rem">
    <span><span class="badge" style="background:#6366f1">&nbsp;</span> {{ __('Waiting') }}</span>
    <span><span class="badge" style="background:#f97316">&nbsp;</span> {{ __('Processing') }}</span>
    <span><span class="badge" style="background:#22c55e">&nbsp;</span> {{ __('Completed') }}</span>
    <span><span class="badge" style="background:#ef4444">&nbsp;</span> {{ __('Failed') }}</span>
    <span><span class="badge" style="background:#94a3b8">&nbsp;</span> {{ __('Paused') }}</span>
    <span><span class="badge" style="background:#f59e0b">&nbsp;</span> {{ __('Conflict — same phonebook within 24 hrs') }}</span>
</div>

{{-- Conflict alert (populated via JS) --}}
<div id="conflictAlert" class="alert alert-warning alert-dismissible py-2 d-none" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-1"></i>
    <strong>{{ __('Conflicts detected:') }}</strong> <span id="conflictText"></span>
    {{ __('Two or more campaigns target the same phonebook within 24 hours.') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>

{{-- Calendar --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-3">
        <div id="calendar"></div>
    </div>
</div>

{{-- Campaign detail modal --}}
<div class="modal fade" id="campaignModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header py-2 border-bottom">
                <h6 class="modal-title fw-semibold" id="modalTitle">—</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <dl class="row mb-0" style="font-size:0.88rem">
                    <dt class="col-4 text-muted fw-normal">{{ __('Status') }}</dt>
                    <dd class="col-8 mb-2" id="modalStatus">—</dd>
                    <dt class="col-4 text-muted fw-normal">{{ __('Type') }}</dt>
                    <dd class="col-8 mb-2" id="modalType">—</dd>
                    <dt class="col-4 text-muted fw-normal">{{ __('Phonebook') }}</dt>
                    <dd class="col-8 mb-2" id="modalPhonebook">—</dd>
                    <dt class="col-4 text-muted fw-normal">{{ __('Scheduled') }}</dt>
                    <dd class="col-8 mb-2" id="modalDate">—</dd>
                    <dt class="col-4 text-muted fw-normal" id="conflictLabelRow" style="display:none">{{ __('Warning') }}</dt>
                    <dd class="col-8 mb-2" id="modalConflict" style="display:none">
                        <span class="text-warning fw-semibold"><i class="bi bi-exclamation-triangle-fill me-1"></i>{{ __('Conflict: same phonebook scheduled within 24 hrs') }}</span>
                    </dd>
                </dl>
            </div>
            <div class="modal-footer py-2 border-top">
                <a id="modalEditBtn" href="#" class="btn btn-sm btn-primary">
                    <i class="bi bi-pencil-fill me-1"></i>{{ __('View Campaign') }}
                </a>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');
    const modal      = new bootstrap.Modal(document.getElementById('campaignModal'));

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left:   'prev,next today',
            center: 'title',
            right:  'dayGridMonth,timeGridWeek,listWeek'
        },
        height: 'auto',
        eventSources: [{
            url: '{{ route("calendar.events") }}',
            method: 'GET',
            failure: function() {
                console.error('Failed to load calendar events');
            }
        }],
        editable: true,
        eventDrop: function(info) {
            const id    = info.event.id;
            const start = info.event.startStr;

            fetch('{{ url("calendar") }}/' + id + '/reschedule', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ start: start })
            })
            .then(r => r.json())
            .then(data => {
                if (!data.ok) info.revert();
                else calendar.refetchEvents();
            })
            .catch(() => info.revert());
        },
        eventClick: function(info) {
            const e     = info.event;
            const props = e.extendedProps;

            document.getElementById('modalTitle').textContent    = e.title;
            document.getElementById('modalStatus').textContent   = props.status;
            document.getElementById('modalType').textContent     = props.type || '—';
            document.getElementById('modalPhonebook').textContent = props.phonebook;
            document.getElementById('modalDate').textContent     = props.scheduled
                ? new Date(e.start).toLocaleString()
                : '{{ __("Not scheduled (shown on creation date)") }}';

            const conflictRows = [
                document.getElementById('conflictLabelRow'),
                document.getElementById('modalConflict')
            ];
            conflictRows.forEach(el => el.style.display = props.conflict ? '' : 'none');

            document.getElementById('modalEditBtn').href = '{{ url("campaigns") }}/' + e.id;
            modal.show();
        },
        eventDidMount: function(info) {
            if (!info.event.extendedProps.editable && info.event.extendedProps.status === 'waiting') {
                info.el.style.cursor = 'not-allowed';
            }
        },
        // After events render, check for conflicts and show banner
        eventsSet: function(events) {
            const conflicting = events.filter(e => e.extendedProps.conflict);
            const alertEl     = document.getElementById('conflictAlert');
            const textEl      = document.getElementById('conflictText');

            if (conflicting.length > 0) {
                const names = [...new Set(conflicting.map(e => e.title))].join(', ');
                textEl.textContent = names + '. ';
                alertEl.classList.remove('d-none');
            } else {
                alertEl.classList.add('d-none');
            }
        }
    });

    calendar.render();
});
</script>
<style>
#calendar .fc-event { cursor: pointer; font-size: 0.78rem; }
#calendar .fc-toolbar-title { font-size: 1.1rem; font-weight: 600; }
</style>
@endpush

</x-layout-dashboard>
