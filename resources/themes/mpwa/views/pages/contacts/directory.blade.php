<x-layout-dashboard title="{{ __('Contacts') }}">

    <link href="{{ asset('assets/plugins/select2/css/select2.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/select2/css/select2-bootstrap4.css') }}" rel="stylesheet" />

    @if (session()->has('alert'))
        <x-alert>
            @slot('type', session('alert')['type'])
            @slot('msg', session('alert')['msg'])
        </x-alert>
    @endif

    <x-page-header title="{{ __('Contacts') }}"
        subtitle="{{ __('Your CRM contact list, phonebooks and imports — all in one place') }}"
        :breadcrumb="[__('Contacts')]">
        <button type="button" class="btn btn-primary btn-sm" id="openAddContact">
            <i class="bi bi-person-plus me-1"></i>{{ __('Add Contact') }}
        </button>
        <a href="{{ route('contacts.import') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-upload me-1"></i>{{ __('Import') }}
        </a>
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#managePhonebooks">
            <i class="bi bi-telephone-fill me-1"></i>{{ __('Manage Phonebooks') }}
        </button>
    </x-page-header>

    <div class="card">
        <div class="card-body pb-2">
            <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
                <div class="input-group input-group-sm" style="max-width:280px">
                    <span class="input-group-text bg-transparent"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="{{ __('Search name, number, company, email…') }}" value="{{ $q }}">
                </div>
                <select name="tag_id" class="form-select form-select-sm" style="max-width:220px" onchange="this.form.submit()">
                    <option value="">{{ __('All Phonebooks') }}</option>
                    @foreach($phonebooks as $pb)
                        <option value="{{ $pb->id }}" {{ (string) request('tag_id') === (string) $pb->id ? 'selected' : '' }}>
                            {{ $pb->name }} ({{ $pb->contacts_count }})
                        </option>
                    @endforeach
                </select>
                <select name="status" class="form-select form-select-sm" style="max-width:180px" onchange="this.form.submit()">
                    <option value="">{{ __('All Statuses') }}</option>
                    @foreach(\App\Models\Contact::STATUSES as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $status }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('Filter') }}</button>
                @if($q || request('tag_id') || request('status'))
                    <a href="{{ route('contacts.directory') }}" class="btn btn-sm btn-outline-secondary">{{ __('Clear') }}</a>
                @endif
            </form>

            @if($contacts->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="bi bi-people d-block mb-2" style="font-size:2.5rem"></i>
                    @if($q || request('tag_id') || request('status'))
                        {{ __('No contacts match your filters.') }}
                    @else
                        {{ __('No contacts yet. Add one or import a CSV/Excel file.') }}
                    @endif
                </div>
            @else
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-2">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Number') }}</th>
                            <th>{{ __('Company') }}</th>
                            <th>{{ __('Email') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Phonebooks') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($contacts as $contact)
                        @php
                            $statusColors = ['Lead' => 'secondary', 'Contacted' => 'info', 'Customer' => 'success', 'Churned' => 'danger'];
                        @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold"
                                         style="width:30px;height:30px;font-size:0.8rem;flex-shrink:0">
                                        {{ strtoupper(mb_substr($contact->name ?: $contact->number, 0, 1)) }}
                                    </div>
                                    <span class="fw-semibold">{{ $contact->name ?: '—' }}</span>
                                </div>
                            </td>
                            <td class="font-monospace small">{{ $contact->number }}</td>
                            <td class="small">{{ $contact->company ?: '—' }}</td>
                            <td class="small">{{ $contact->email ?: '—' }}</td>
                            <td>
                                @if($contact->status)
                                    <span class="badge bg-{{ $statusColors[$contact->status] ?? 'secondary' }}-subtle text-{{ $statusColors[$contact->status] ?? 'secondary' }}">{{ $contact->status }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @forelse($contact->tags as $tag)
                                    <span class="badge bg-secondary-subtle text-secondary">{{ $tag->name }}</span>
                                @empty
                                    <span class="text-muted">—</span>
                                @endforelse
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('contact.timeline', $contact->number) }}"
                                   class="btn btn-sm btn-outline-primary" title="{{ __('360° Profile') }}">
                                    <i class="bi bi-person-lines-fill"></i>
                                </a>
                                @php $conv = $convMap->get($contact->number); @endphp
                                @if($conv)
                                    <a href="{{ route('chat.show', $conv->id) }}?conv_status={{ $conv->conversation_status }}"
                                       class="btn btn-sm btn-outline-secondary" title="{{ __('Open Chat') }}">
                                        <i class="bi bi-chat-dots"></i>
                                    </a>
                                @else
                                    <span class="btn btn-sm btn-outline-secondary disabled" title="{{ __('No conversation yet') }}">
                                        <i class="bi bi-chat-dots"></i>
                                    </span>
                                @endif
                                <button type="button" class="btn btn-sm btn-outline-secondary js-edit-contact" title="{{ __('Edit') }}"
                                    data-id="{{ $contact->id }}"
                                    data-name="{{ $contact->name }}"
                                    data-number="{{ $contact->number }}"
                                    data-company="{{ $contact->company }}"
                                    data-email="{{ $contact->email }}"
                                    data-address="{{ $contact->address }}"
                                    data-linkedin_url="{{ $contact->linkedin_url }}"
                                    data-facebook_url="{{ $contact->facebook_url }}"
                                    data-website="{{ $contact->website }}"
                                    data-source="{{ $contact->source }}"
                                    data-status="{{ $contact->status }}"
                                    data-remarks="{{ $contact->remarks }}"
                                    data-tag-ids="{{ $contact->tags->pluck('id')->implode(',') }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger js-delete-contact" title="{{ __('Delete') }}"
                                    data-id="{{ $contact->id }}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex align-items-center justify-content-between">
                <small class="text-muted">
                    {{ __('Showing :from–:to of :total', ['from' => $contacts->firstItem(), 'to' => $contacts->lastItem(), 'total' => $contacts->total()]) }}
                </small>
                {{ $contacts->links() }}
            </div>
            @endif
        </div>
    </div>

    {{-- ── Add / Edit Contact modal ─────────────────────────────────────── --}}
    <div class="modal fade" id="contactModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="contactModalTitle">{{ __('Add Contact') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="contactForm">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label small">{{ __('Name') }}</label>
                                <input type="text" name="name" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">{{ __('Number (with country code)') }} *</label>
                                <input type="text" name="number" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">{{ __('Company') }}</label>
                                <input type="text" name="company" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">{{ __('Email') }}</label>
                                <input type="email" name="email" class="form-control form-control-sm">
                            </div>
                            <div class="col-12">
                                <label class="form-label small">{{ __('Address') }}</label>
                                <input type="text" name="address" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">{{ __('LinkedIn URL') }}</label>
                                <input type="url" name="linkedin_url" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">{{ __('Facebook URL') }}</label>
                                <input type="url" name="facebook_url" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">{{ __('Website') }}</label>
                                <input type="url" name="website" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">{{ __('Source') }}</label>
                                <input type="text" name="source" class="form-control form-control-sm" placeholder="{{ __('e.g. LinkedIn scrape, Referral') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">{{ __('Status') }}</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">{{ __('—') }}</option>
                                    @foreach(\App\Models\Contact::STATUSES as $status)
                                        <option value="{{ $status }}">{{ $status }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">{{ __('Remarks') }}</label>
                                <textarea name="remarks" class="form-control form-control-sm" rows="2"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">{{ __('Phonebooks') }}</label>
                                <select name="tag_ids[]" id="contactTagIds" class="form-control" multiple data-placeholder="{{ __('Select phonebooks…') }}">
                                    @foreach($phonebooks as $pb)
                                        <option value="{{ $pb->id }}">{{ $pb->name }}</option>
                                    @endforeach
                                </select>
                                <div class="input-group input-group-sm mt-1" style="max-width:320px">
                                    <input type="text" id="newPhonebookName" class="form-control" placeholder="{{ __('+ New phonebook name') }}">
                                    <button type="button" id="addPhonebookInline" class="btn btn-outline-secondary">{{ __('Add') }}</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="button" class="btn btn-primary btn-sm" id="saveContactBtn">{{ __('Save') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Manage Phonebooks offcanvas ──────────────────────────────────── --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="managePhonebooks">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">{{ __('Manage Phonebooks') }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <form action="{{ route('tag.store') }}" method="POST" class="d-flex gap-2 mb-3">
                @csrf
                <input type="text" name="name" class="form-control form-control-sm" placeholder="{{ __('New phonebook name') }}" required minlength="3">
                <button type="submit" class="btn btn-sm btn-primary text-nowrap">{{ __('Add') }}</button>
            </form>
            <form action="{{ route('fetch.groups') }}" method="post" class="mb-3">
                @csrf
                <input type="hidden" name="device" value="{{ Session::has('selectedDevice') ? Session::get('selectedDevice')['device_id'] : '' }}">
                <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                    {{ __('Sync WhatsApp Groups as Phonebooks') }} <i class="bi bi-whatsapp"></i>
                </button>
            </form>
            <hr>
            @forelse($phonebooks as $pb)
                <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                    <span>{{ $pb->name }} <span class="text-muted small">({{ $pb->contacts_count }})</span></span>
                    <form action="{{ route('tag.delete') }}" method="POST"
                        onsubmit="return confirm('{{ __('Delete this phonebook? Contacts will be kept but unassigned from it.') }}')">
                        @method('delete')
                        @csrf
                        <input type="hidden" name="id" value="{{ $pb->id }}">
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </div>
            @empty
                <div class="text-muted small">{{ __('No phonebooks yet.') }}</div>
            @endforelse
        </div>
    </div>

    <script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
    <script src="{{ asset('assets/js/form-select2.js') }}"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const csrf = $('meta[name="csrf-token"]').attr('content');
        const modalEl = document.getElementById('contactModal');
        const modal = new bootstrap.Modal(modalEl);
        const form = document.getElementById('contactForm');
        let editingId = null;

        // Not using the global .multiple-select auto-init: select2's dropdown
        // needs an explicit dropdownParent to render inside a Bootstrap modal
        // (the modal was display:none at page load, when the global init ran).
        $('#contactTagIds').select2({
            theme: 'bootstrap4',
            width: '100%',
            placeholder: $('#contactTagIds').data('placeholder'),
            dropdownParent: $('#contactModal'),
        });

        function resetForm() {
            form.reset();
            $('#contactTagIds').val(null).trigger('change');
            editingId = null;
            document.getElementById('contactModalTitle').textContent = '{{ __('Add Contact') }}';
        }

        document.getElementById('openAddContact').addEventListener('click', function () {
            resetForm();
            modal.show();
        });

        document.querySelectorAll('.js-edit-contact').forEach(function (btn) {
            btn.addEventListener('click', function () {
                resetForm();
                editingId = btn.dataset.id;
                document.getElementById('contactModalTitle').textContent = '{{ __('Edit Contact') }}';
                ['name','number','company','email','address','linkedin_url','facebook_url','website','source','status','remarks'].forEach(function (field) {
                    const el = form.querySelector('[name="' + field + '"]');
                    if (el) el.value = btn.dataset[field] || '';
                });
                const tagIds = (btn.dataset.tagIds || '').split(',').filter(Boolean);
                $('#contactTagIds').val(tagIds).trigger('change');
                modal.show();
            });
        });

        document.getElementById('saveContactBtn').addEventListener('click', function () {
            const data = $(form).serialize();
            const url = editingId ? `{{ url('/contacts') }}/${editingId}` : `{{ route('contact.store') }}`;
            const method = editingId ? 'PUT' : 'POST';

            $.ajax({
                url: url,
                method: method,
                data: data,
                headers: { 'X-CSRF-TOKEN': csrf },
                dataType: 'json',
            }).done(function (res) {
                if (res.error) { toastr['error'](res.msg); return; }
                toastr['success'](res.msg);
                modal.hide();
                window.location.reload();
            }).fail(function () {
                toastr['error']('{{ __('Something went wrong.') }}');
            });
        });

        document.querySelectorAll('.js-delete-contact').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!confirm('{{ __('Delete this contact permanently?') }}')) return;
                $.ajax({
                    url: `{{ url('/contact/delete') }}/${btn.dataset.id}`,
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrf },
                    dataType: 'json',
                }).done(function (res) {
                    if (res.error) { toastr['error'](res.msg); return; }
                    toastr['success'](res.msg);
                    window.location.reload();
                });
            });
        });

        document.getElementById('addPhonebookInline').addEventListener('click', function () {
            const nameInput = document.getElementById('newPhonebookName');
            const name = nameInput.value.trim();
            if (name.length < 3) { toastr['warning']('{{ __('Phonebook name must be at least 3 characters.') }}'); return; }

            $.ajax({
                url: `{{ route('tag.store') }}`,
                method: 'POST',
                data: { name: name },
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                dataType: 'json',
            }).done(function (res) {
                if (res.error) { toastr['error'](res.msg); return; }
                const option = new Option(res.tag.name, res.tag.id, true, true);
                $('#contactTagIds').append(option).trigger('change');
                nameInput.value = '';
            }).fail(function (xhr) {
                toastr['error'](xhr.responseJSON?.message || '{{ __('Could not add phonebook.') }}');
            });
        });
    });
    </script>
</x-layout-dashboard>
