<x-layout-dashboard title="{{ __('Import Contacts') }}">

    <x-page-header title="{{ __('Import Contacts from CSV / Excel') }}"
        subtitle="{{ __('Upload a spreadsheet, map columns, and bulk-import contacts into a phonebook.') }}"
        :breadcrumb="[__('Contacts'), __('Import')]">
        <a href="{{ route('contacts.directory') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> {{ __('Contacts') }}
        </a>
    </x-page-header>

            {{-- Stage 1: Upload --}}
            <div id="stage-upload">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('Step 1 — Upload File') }}</h5>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('File') }} <span class="text-danger">*</span></label>
                                <input type="file" id="importFile" class="form-control" accept=".csv,.xlsx,.xls">
                                <div class="form-text">{{ __('Accepted formats: CSV, XLSX, XLS. Max 10MB.') }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">{{ __('Phonebook') }} <span class="text-danger">*</span></label>
                                <select id="phonebookSelect" class="form-select">
                                    <option value="">{{ __('Select existing phonebook') }}</option>
                                    @foreach ($phonebooks as $pb)
                                        <option value="{{ $pb->id }}">{{ $pb->name }} ({{ $pb->contacts_count }} {{ __('contacts') }})</option>
                                    @endforeach
                                    <option value="new">{{ __('+ Create new phonebook') }}</option>
                                </select>
                            </div>
                            <div class="col-md-6 d-none" id="newPhonebookInput">
                                <label class="form-label fw-semibold">{{ __('New Phonebook Name') }}</label>
                                <input type="text" id="newPhonebookName" class="form-control" placeholder="{{ __('e.g. Black Friday Leads') }}">
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="button" id="previewBtn" class="btn btn-primary">
                                <i class="bi bi-table"></i> {{ __('Preview & Map Columns') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stage 2: Column Mapping --}}
            <div id="stage-mapping" class="d-none">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('Step 2 — Map Columns') }}</h5>
                        <p class="text-muted">{{ __('Assign a role to each column. At minimum, Phone Number is required.') }}</p>

                        <div class="table-responsive mb-3">
                            <table class="table table-bordered align-middle" id="mappingTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('Column #') }}</th>
                                        <th>{{ __('Header / Sample Data') }}</th>
                                        <th>{{ __('Role') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="mappingBody"></tbody>
                            </table>
                        </div>

                        <div class="alert alert-light border mb-3">
                            <strong>{{ __('Data Preview') }} ({{ __('first 5 rows') }}):</strong>
                            <div class="table-responsive mt-2">
                                <table class="table table-sm table-bordered" id="previewTable"></table>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" id="backBtn" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> {{ __('Back') }}
                            </button>
                            <button type="button" id="importBtn" class="btn btn-primary">
                                <i class="bi bi-upload"></i> {{ __('Import Contacts') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

<script>
const ROLE_OPTIONS = `
    <option value="skip">— {{ __('Skip') }}</option>
    <option value="phone">📱 {{ __('Phone Number') }}</option>
    <option value="name">👤 {{ __('Contact Name') }}</option>
    <option value="company">🏢 {{ __('Company') }}</option>
    <option value="email">✉️ {{ __('Email') }}</option>
    <option value="address">📍 {{ __('Address') }}</option>
    <option value="linkedin">🔗 {{ __('LinkedIn URL') }}</option>
    <option value="facebook">🔗 {{ __('Facebook URL') }}</option>
    <option value="website">🌐 {{ __('Website') }}</option>
    <option value="source">🏷️ {{ __('Source') }}</option>
    <option value="status">📌 {{ __('Status') }}</option>
    <option value="remarks">📝 {{ __('Remarks') }}</option>
    <option value="var1">@{{1}} {{ __('Variable 1') }}</option>
    <option value="var2">@{{2}} {{ __('Variable 2') }}</option>
    <option value="var3">@{{3}} {{ __('Variable 3') }}</option>
`;

// role -> FormData field name (all *_col except phone/name which the backend already expects that way)
const ROLE_COL_PARAM = {
    phone: 'phone_col', name: 'name_col', company: 'company_col', email: 'email_col',
    address: 'address_col', linkedin: 'linkedin_col', facebook: 'facebook_col',
    website: 'website_col', source: 'source_col', status: 'status_col', remarks: 'remarks_col',
};

let uploadedHeaders = [];
let uploadedRows = [];
let totalRows = 0;

$('#phonebookSelect').on('change', function () {
    $('#newPhonebookInput').toggleClass('d-none', $(this).val() !== 'new');
});

$('#previewBtn').on('click', function () {
    const file = $('#importFile')[0].files[0];
    if (!file) { toastr.error('{{ __("Please select a file") }}'); return; }

    const pb = $('#phonebookSelect').val();
    if (!pb) { toastr.error('{{ __("Please select a phonebook") }}'); return; }
    if (pb === 'new' && !$('#newPhonebookName').val().trim()) {
        toastr.error('{{ __("Please enter a phonebook name") }}'); return;
    }

    const fd = new FormData();
    fd.append('file', file);
    fd.append('_token', $('meta[name="csrf-token"]').attr('content'));

    $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> {{ __("Reading file...") }}');

    $.ajax({
        method: 'POST',
        url: '{{ route("contacts.import.preview") }}',
        data: fd,
        processData: false,
        contentType: false,
        success: (res) => {
            uploadedHeaders = res.headers;
            uploadedRows = res.rows;
            totalRows = res.total;
            renderMapping();
            renderPreview();
            $('#stage-upload').addClass('d-none');
            $('#stage-mapping').removeClass('d-none');
            $('#previewBtn').prop('disabled', false).html('<i class="bi bi-table"></i> {{ __("Preview & Map Columns") }}');
        },
        error: (err) => {
            toastr.error(err.responseJSON?.message ?? '{{ __("Preview failed") }}');
            $('#previewBtn').prop('disabled', false).html('<i class="bi bi-table"></i> {{ __("Preview & Map Columns") }}');
        },
    });
});

function renderMapping() {
    let html = '';
    uploadedHeaders.forEach((header, i) => {
        const sample = uploadedRows.map(r => r[i] ?? '').filter(Boolean).slice(0, 2).join(', ');
        // Auto-detect phone/name columns
        html += `<tr>
            <td class="text-center fw-bold">${i}</td>
            <td><strong>${header}</strong><br><small class="text-muted">${sample}</small></td>
            <td><select class="form-select col-role" data-col="${i}">${ROLE_OPTIONS}</select></td>
        </tr>`;
    });
    $('#mappingBody').html(html);

    // Apply auto-detected values
    const AUTO_DETECT = [
        [/phone|mobile|number|tel|hp|wa|whatsapp/, 'phone'],
        [/^name$|nama|contact.?name|full.?name/, 'name'],
        [/company|organi[sz]ation|organi[sz]ation/, 'company'],
        [/e.?mail/, 'email'],
        [/address/, 'address'],
        [/linkedin/, 'linkedin'],
        [/facebook/, 'facebook'],
        [/website|web.?site/, 'website'],
        [/source/, 'source'],
        [/status/, 'status'],
        [/remarks|notes/, 'remarks'],
    ];
    uploadedHeaders.forEach((header, i) => {
        const headerLower = String(header).toLowerCase();
        const match = AUTO_DETECT.find(([re]) => re.test(headerLower));
        if (match) $(`.col-role[data-col=${i}]`).val(match[1]);
    });
}

function renderPreview() {
    let html = '<thead class="table-light"><tr>';
    uploadedHeaders.forEach(h => html += `<th>${h}</th>`);
    html += '</tr></thead><tbody>';
    uploadedRows.forEach(row => {
        html += '<tr>';
        (Array.isArray(row) ? row : Object.values(row)).forEach(cell => html += `<td>${cell ?? ''}</td>`);
        html += '</tr>';
    });
    html += '</tbody>';
    $('#previewTable').html(html);
}

$('#backBtn').on('click', function () {
    $('#stage-mapping').addClass('d-none');
    $('#stage-upload').removeClass('d-none');
});

$('#importBtn').on('click', function () {
    const mapped = {};
    $('.col-role').each(function () {
        const role = $(this).val();
        const col = parseInt($(this).data('col'));
        if (role !== 'skip' && ROLE_COL_PARAM[role]) mapped[role] = col;
    });

    if (mapped.phone === undefined) { toastr.error('{{ __("Please assign the Phone Number column") }}'); return; }

    const file = $('#importFile')[0].files[0];
    const fd = new FormData();
    fd.append('file', file);
    Object.entries(mapped).forEach(([role, col]) => fd.append(ROLE_COL_PARAM[role], col));
    if ($('#phonebookSelect').val() === 'new') {
        fd.append('new_phonebook_name', $('#newPhonebookName').val());
    } else {
        fd.append('phonebook_id', $('#phonebookSelect').val());
    }
    fd.append('_token', $('meta[name="csrf-token"]').attr('content'));

    $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> {{ __("Importing...") }}');

    $.ajax({
        method: 'POST',
        url: '{{ route("contacts.import.store") }}',
        data: fd,
        processData: false,
        contentType: false,
        success: (res) => {
            toastr.success(res.message);
            setTimeout(() => window.location = '{{ route("contacts.directory") }}', 1500);
        },
        error: (err) => {
            toastr.error(err.responseJSON?.message ?? '{{ __("Import failed") }}');
            $('#importBtn').prop('disabled', false).html('<i class="bi bi-upload"></i> {{ __("Import Contacts") }}');
        },
    });
});
</script>

</x-layout-dashboard>
