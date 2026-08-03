<x-layout-dashboard title="Template Library">

<x-page-header title="{{ __('Template Library') }}"
    subtitle="{{ __('Pre-approved Meta templates — sync once, browse anytime without hitting the API again.') }}"
    :breadcrumb="[__('Templates'), __('Library')]" />

{{-- ── Toolbar ───────────────────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">

        {{-- Row 1: sync device + action buttons --}}
        <div class="d-flex flex-wrap align-items-end gap-2 mb-3">
            <div>
                <label class="form-label form-label-sm fw-semibold mb-1">{{ __('Device') }} <span class="text-danger">*</span></label>
                <select id="syncDevice" class="form-select form-select-sm" style="min-width:200px">
                    <option value="">{{ __('Select device for sync…') }}</option>
                    @foreach($devices as $d)
                        <option value="{{ $d->id }}">{{ $d->meta_profile['verified_name'] ?? $d->body }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ms-auto d-flex gap-2 align-items-end">
                <button class="btn btn-sm btn-success" id="syncBtn" onclick="syncLibrary()">
                    <i class="bi bi-arrow-repeat me-1"></i>{{ __('Sync from Meta') }}
                </button>
                <a href="{{ route('templates.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>{{ __('My Templates') }}
                </a>
            </div>
        </div>

        {{-- Row 2: filters --}}
        <div class="row g-2 align-items-end">

            {{-- Category --}}
            <div class="col-6 col-sm-auto">
                <label class="form-label form-label-sm fw-semibold mb-1">{{ __('Category') }}</label>
                <select id="filterCategory" class="form-select form-select-sm" onchange="applyFilters()">
                    <option value="" {{ !$category ? 'selected' : '' }}>{{ __('All Categories') }}</option>
                    <option value="MARKETING"      {{ $category === 'MARKETING'      ? 'selected' : '' }}>{{ __('Marketing') }}</option>
                    <option value="UTILITY"        {{ $category === 'UTILITY'        ? 'selected' : '' }}>{{ __('Utility') }}</option>
                    <option value="AUTHENTICATION" {{ $category === 'AUTHENTICATION' ? 'selected' : '' }}>{{ __('Authentication') }}</option>
                </select>
            </div>

            {{-- Industry --}}
            <div class="col-6 col-sm-auto">
                <label class="form-label form-label-sm fw-semibold mb-1">{{ __('Industry') }}</label>
                <select id="filterIndustry" class="form-select form-select-sm" onchange="applyFilters()" style="min-width:180px">
                    <option value="" {{ !$industry ? 'selected' : '' }}>{{ __('All Industries') }}</option>
                    @if($industries->isNotEmpty())
                        @foreach($industries as $ind)
                            <option value="{{ $ind }}" {{ $industry === $ind ? 'selected' : '' }}>
                                {{ ucwords(strtolower(str_replace('_', ' ', $ind))) }}
                            </option>
                        @endforeach
                    @else
                        {{-- Predefined fallback before first sync --}}
                        @foreach([
                            'ECOMMERCE'          => 'E-Commerce',
                            'FINANCIAL_SERVICES' => 'Financial Services',
                            'TELECOMMUNICATION'  => 'Telecommunication',
                            'HEALTHCARE'         => 'Healthcare',
                            'EDUCATION'          => 'Education',
                            'RETAIL'             => 'Retail',
                            'TRAVEL'             => 'Travel',
                            'TECHNOLOGY'         => 'Technology',
                            'FOOD_AND_BEVERAGE'  => 'Food & Beverage',
                            'REAL_ESTATE'        => 'Real Estate',
                            'PROFESSIONAL_SERVICES' => 'Professional Services',
                            'NON_PROFIT'         => 'Non-Profit',
                            'OTHER'              => 'Other',
                        ] as $val => $lbl)
                            <option value="{{ $val }}" {{ $industry === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            {{-- Use Case --}}
            <div class="col-6 col-sm-auto">
                <label class="form-label form-label-sm fw-semibold mb-1">{{ __('Use Case') }}</label>
                <select id="filterUsecase" class="form-select form-select-sm" onchange="applyFilters()" style="min-width:210px">
                    <option value="" {{ !$usecase ? 'selected' : '' }}>{{ __('All Use Cases') }}</option>
                    @if($usecases->isNotEmpty())
                        @foreach($usecases as $uc)
                            <option value="{{ $uc }}" {{ $usecase === $uc ? 'selected' : '' }}>
                                {{ ucwords(strtolower(str_replace('_', ' ', $uc))) }}
                            </option>
                        @endforeach
                    @else
                        {{-- Predefined fallback before first sync --}}
                        @foreach([
                            'FLOW'                          => 'Flow',
                            'ACCOUNT_OR_PRODUCT_PROTECTION' => 'Account / Product Protection',
                            'ACCOUNT_UPDATES'               => 'Account Updates',
                            'CALL_PERMISSIONS'              => 'Call Permissions',
                            'CUSTOMER_FEEDBACK'             => 'Customer Feedback',
                            'EVENT_REMINDER'                => 'Event Reminder',
                            'GROUP_INVITATION_LINK'         => 'Group Invitation Link',
                            'LEGAL_REGULATORY_COMPLIANCE'   => 'Legal / Regulatory Compliance',
                            'ORDER_MANAGEMENT'              => 'Order Management',
                            'PAYMENTS'                      => 'Payments',
                            'PUBLIC_DISRUPTION'             => 'Public Disruption',
                            'PUBLIC_SAFETY'                 => 'Public Safety',
                            'PUBLIC_SERVICE'                => 'Public Service',
                        ] as $val => $lbl)
                            <option value="{{ $val }}" {{ $usecase === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            {{-- Language --}}
            <div class="col-6 col-sm-auto">
                <label class="form-label form-label-sm fw-semibold mb-1">{{ __('Language') }}</label>
                <select id="filterLanguage" class="form-select form-select-sm" onchange="applyFilters()" style="min-width:155px">
                    <option value="" {{ !$language ? 'selected' : '' }}>{{ __('All Languages') }}</option>
                    @foreach([
                        'en'=>'English','en_US'=>'English (US)','en_GB'=>'English (UK)',
                        'hi'=>'Hindi','ar'=>'Arabic','es'=>'Spanish','es_ES'=>'Spanish (ES)',
                        'pt_BR'=>'Portuguese (BR)','fr'=>'French','de'=>'German',
                        'it'=>'Italian','id'=>'Indonesian','ms'=>'Malay',
                        'tr'=>'Turkish','ru'=>'Russian','ja'=>'Japanese',
                        'ko'=>'Korean','zh_CN'=>'Chinese (Simplified)',
                    ] as $code => $lbl)
                        <option value="{{ $code }}" {{ $language === $code ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Search --}}
            <div class="col-12 col-sm">
                <label class="form-label form-label-sm fw-semibold mb-1">{{ __('Search') }}</label>
                <div class="input-group input-group-sm">
                    <input type="text" id="filterSearch" class="form-control form-control-sm"
                           placeholder="{{ __('Template name, use case, industry…') }}"
                           value="{{ $search }}"
                           onkeydown="if(event.key==='Enter') applyFilters()">
                    @if($category || $industry || $usecase || $language || $search)
                    <a href="{{ route('templates.library') }}" class="btn btn-outline-secondary" title="{{ __('Clear filters') }}">
                        <i class="bi bi-x-lg"></i>
                    </a>
                    @endif
                    <button class="btn btn-outline-secondary" onclick="applyFilters()">
                        <i class="bi bi-funnel"></i>
                    </button>
                </div>
            </div>

        </div>

        {{-- Status bar --}}
        <div class="d-flex align-items-center flex-wrap gap-3 mt-3 pt-2 border-top">
            <span class="small text-muted">
                <i class="bi bi-database me-1"></i>
                <strong id="totalCount">{{ $total }}</strong> {{ __('templates in local DB') }}
            </span>
            @if($lastSync)
            <span class="small text-muted">
                <i class="bi bi-clock me-1"></i>{{ __('Last synced') }}: <strong>{{ \Carbon\Carbon::parse($lastSync)->diffForHumans() }}</strong>
            </span>
            @else
            <span class="small text-warning">
                <i class="bi bi-exclamation-triangle me-1"></i>{{ __('Never synced — select a device and click Sync from Meta.') }}
            </span>
            @endif
            <div id="syncProgress" class="ms-auto" style="display:none">
                <div class="spinner-border spinner-border-sm text-success me-1"></div>
                <span class="small text-muted" id="syncMsg">{{ __('Syncing…') }}</span>
            </div>
        </div>
    </div>
</div>

{{-- ── Template grid ─────────────────────────────────────────────────────────── --}}
@if($templates->isEmpty())
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-collection fs-1 d-block mb-3 opacity-25"></i>
            @if($total === 0)
                <p class="fw-semibold mb-1">{{ __('Library is empty') }}</p>
                <p class="small mb-3">{{ __('Select a device above and click') }} <strong>{{ __('Sync from Meta') }}</strong> {{ __('to load pre-approved templates.') }}</p>
            @else
                <p class="fw-semibold mb-1">{{ __('No templates match your filters') }}</p>
                <a href="{{ route('templates.library') }}" class="btn btn-sm btn-outline-secondary">{{ __('Clear filters') }}</a>
            @endif
        </div>
    </div>
@else
    <div class="row g-3" id="templateGrid">
        @foreach($templates as $tpl)
        @php
            $catColor = $tpl->category === 'MARKETING' ? 'warning text-dark' : ($tpl->category === 'UTILITY' ? 'info text-dark' : 'success');
            $buttons  = $tpl->buttons ?? [];
        @endphp
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 shadow-sm h-100 tpl-lib-card" style="cursor:pointer" onclick="previewTemplate({{ $tpl->id }})">

                {{-- Card header: name + badges --}}
                <div class="card-header bg-transparent border-bottom px-3 pt-3 pb-2">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <p class="fw-semibold mb-1 small text-break me-auto" style="font-size:12px;line-height:1.3">{{ $tpl->name }}</p>
                        <span class="badge bg-{{ $catColor }} flex-shrink-0" style="font-size:10px">{{ $tpl->category }}</span>
                    </div>
                    <div class="d-flex flex-wrap gap-1">
                        <span class="badge bg-light text-muted border" style="font-size:10px">{{ strtoupper($tpl->language) }}</span>
                        @if($tpl->usecase)
                            <span class="badge bg-light text-muted border" style="font-size:10px">{{ ucwords(strtolower(str_replace('_',' ',$tpl->usecase))) }}</span>
                        @endif
                        @if($tpl->industry)
                            <span class="badge bg-light text-muted border" style="font-size:10px">{{ ucwords(strtolower(str_replace('_',' ',$tpl->industry))) }}</span>
                        @endif
                    </div>
                </div>

                {{-- WhatsApp bubble preview --}}
                <div class="card-body p-3" style="background:#e5ddd5">
                    <div class="d-flex justify-content-flex-end">
                        <div class="ms-auto" style="max-width:90%">
                            <div style="background:#fff;border-radius:8px 0 8px 8px;padding:8px 10px 6px;box-shadow:0 1px 2px rgba(0,0,0,.15);position:relative">
                                {{-- Triangle --}}
                                <div style="position:absolute;top:0;right:-8px;width:0;height:0;border-left:8px solid #fff;border-bottom:8px solid transparent"></div>

                                @if($tpl->header)
                                    <p class="fw-bold mb-1" style="font-size:13px;line-height:1.4;color:#111">{{ $tpl->header }}</p>
                                @endif

                                @if($tpl->body)
                                    <p class="mb-1" style="font-size:13px;line-height:1.5;color:#303030;white-space:pre-wrap;word-break:break-word">{{ $tpl->body }}</p>
                                @else
                                    <p class="text-muted mb-1" style="font-size:12px;font-style:italic">{{ __('No body text') }}</p>
                                @endif

                                @if($tpl->footer)
                                    <p class="mb-0" style="font-size:11px;color:#888;line-height:1.3">{{ $tpl->footer }}</p>
                                @endif

                                {{-- Timestamp --}}
                                <div class="text-end mt-1">
                                    <span style="font-size:10px;color:#aaa">{{ now()->format('H:i') }} <i class="bi bi-check2-all" style="color:#53bdeb"></i></span>
                                </div>
                            </div>

                            {{-- WhatsApp-style buttons --}}
                            @if(count($buttons))
                                <div style="margin-top:2px">
                                    @foreach($buttons as $btn)
                                    <div style="background:#fff;border-radius:8px;padding:7px 10px;text-align:center;margin-top:2px;box-shadow:0 1px 2px rgba(0,0,0,.1)">
                                        <span style="font-size:13px;color:#00a5f4;font-weight:500">
                                            <i class="bi bi-{{ $btn['type'] === 'URL' ? 'box-arrow-up-right' : ($btn['type'] === 'PHONE_NUMBER' ? 'telephone' : 'reply') }} me-1"></i>
                                            {{ $btn['text'] ?? $btn['type'] }}
                                        </span>
                                    </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="card-footer bg-transparent d-flex gap-2 px-3 py-2" onclick="event.stopPropagation()">
                    <button class="btn btn-sm btn-outline-secondary flex-shrink-0"
                            onclick="previewTemplate({{ $tpl->id }})" title="{{ __('Preview & Customize') }}">
                        <i class="bi bi-eye me-1"></i>{{ __('Preview') }}
                    </button>
                    <button class="btn btn-sm btn-success flex-fill add-btn" data-id="{{ $tpl->id }}"
                            onclick="addTemplate({{ $tpl->id }}, this)">
                        <i class="bi bi-plus-lg me-1"></i>{{ __('Use Template') }}
                    </button>
                </div>

            </div>
        </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    <div class="mt-4">{{ $templates->links() }}</div>
@endif

{{-- ── Preview & Customize Modal ────────────────────────────────────────────── --}}
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2">
                <div>
                    <h6 class="modal-title mb-0" id="previewTitle"></h6>
                    <div id="previewBadges" class="d-flex gap-1 mt-1 flex-wrap"></div>
                </div>
                <button type="button" class="btn-close ms-2" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0" style="min-height:420px">

                    {{-- Left: variable customization --}}
                    <div class="col-md-5 border-end p-4" id="customizePanel">
                        <p class="fw-semibold small mb-3"><i class="bi bi-pencil-square me-1"></i>{{ __('Customize Variables') }}</p>
                        <div id="varFields">
                            <p class="text-muted small">{{ __('No variables in this template.') }}</p>
                        </div>

                        {{-- URL button row --}}
                        <div id="urlRow" style="display:none" class="mt-3 pt-3 border-top">
                            <label class="form-label form-label-sm fw-semibold mb-1">
                                <i class="bi bi-link-45deg text-primary me-1"></i>{{ __('URL Button link') }}
                            </label>
                            <input type="url" id="customUrl" class="form-control form-control-sm"
                                   placeholder="https://yoursite.com/page">
                            <div class="form-text">{{ __('Leave blank to keep Meta\'s default URL.') }}</div>
                        </div>

                        <div class="mt-3 pt-3 border-top">
                            <p class="fw-semibold small mb-2"><i class="bi bi-info-circle me-1"></i>{{ __('Template Info') }}</p>
                            <div id="previewMeta" class="small text-muted"></div>
                        </div>
                    </div>

                    {{-- Right: live WhatsApp phone preview --}}
                    <div class="col-md-7 d-flex align-items-center justify-content-center p-4" style="background:#f0f0f0">
                        <div style="width:300px">
                            {{-- Phone chrome --}}
                            <div style="background:#128c7e;border-radius:12px 12px 0 0;padding:10px 14px;display:flex;align-items:center;gap:8px">
                                <div style="width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,.3);display:flex;align-items:center;justify-content:center">
                                    <i class="bi bi-person-fill text-white"></i>
                                </div>
                                <div>
                                    <div style="color:#fff;font-size:13px;font-weight:600">WhatsApp Business</div>
                                    <div style="color:rgba(255,255,255,.75);font-size:11px">online</div>
                                </div>
                            </div>
                            {{-- Chat area --}}
                            <div id="phoneChatArea" style="background:#e5ddd5;padding:12px;min-height:250px;border-radius:0 0 12px 12px">
                                <div class="d-flex justify-content-end">
                                    <div id="livePreviewBubble" style="max-width:90%">
                                        <div id="liveBubble" style="background:#fff;border-radius:8px 0 8px 8px;padding:10px 12px 6px;box-shadow:0 1px 2px rgba(0,0,0,.15);position:relative">
                                            <div style="position:absolute;top:0;right:-8px;width:0;height:0;border-left:8px solid #fff;border-bottom:8px solid transparent"></div>
                                            <div id="liveHeader" style="font-size:13px;font-weight:700;margin-bottom:4px;color:#111;display:none"></div>
                                            <div id="liveBody"   style="font-size:13px;line-height:1.5;color:#303030;white-space:pre-wrap;word-break:break-word"></div>
                                            <div id="liveFooter" style="font-size:11px;color:#888;margin-top:4px;display:none"></div>
                                            <div style="text-align:right;margin-top:4px">
                                                <span style="font-size:10px;color:#aaa" id="liveTime"></span>
                                            </div>
                                        </div>
                                        <div id="liveButtons"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                <button type="button" class="btn btn-sm btn-success" id="previewAddBtn" onclick="addFromPreview()">
                    <i class="bi bi-plus-lg me-1"></i>{{ __('Use Template') }}
                </button>
            </div>
        </div>
    </div>
</div>

@php
    $jsDevices = $devices->map(fn($d) => [
        'id'   => $d->id,
        'name' => $d->meta_profile['verified_name'] ?? $d->body,
    ]);
    $jsTplData = $templates->getCollection()->keyBy('id')->map(fn($t) => [
        'id'       => $t->id,
        'name'     => $t->name,
        'language' => $t->language,
        'category' => $t->category,
        'header'   => $t->header,
        'body'     => $t->body,
        'footer'   => $t->footer,
        'buttons'  => $t->buttons ?? [],
        'usecase'  => $t->usecase,
        'industry' => $t->industry,
    ]);
@endphp
@push('scripts')
<script>
const CSRF      = '{{ csrf_token() }}';
const SYNC_URL  = '{{ route("templates.library.sync") }}';
const ADD_URL   = '{{ route("templates.library.add") }}';
const DEVICES   = @json($jsDevices);
const TPL_DATA  = @json($jsTplData);

let previewActiveId = null;

// ── Device selection persistence ──────────────────────────────────────────────
const DEVICE_KEY = 'tplLibrarySyncDevice';
(function restoreDevice() {
    const saved = localStorage.getItem(DEVICE_KEY);
    if (!saved) return;
    const sel = document.getElementById('syncDevice');
    if ([...sel.options].some(o => o.value === saved)) sel.value = saved;
})();
document.getElementById('syncDevice').addEventListener('change', function () {
    localStorage.setItem(DEVICE_KEY, this.value);
});

// ── Filters ──────────────────────────────────────────────────────────────────
function applyFilters() {
    const params = new URLSearchParams();
    const cat      = document.getElementById('filterCategory').value;
    const industry = document.getElementById('filterIndustry').value;
    const usecase  = document.getElementById('filterUsecase').value;
    const lang     = document.getElementById('filterLanguage').value;
    const q        = document.getElementById('filterSearch').value.trim();
    if (cat)      params.set('category', cat);
    if (industry) params.set('industry', industry);
    if (usecase)  params.set('usecase', usecase);
    if (lang)     params.set('language', lang);
    if (q)        params.set('search', q);
    window.location.href = '{{ route("templates.library") }}' + (params.toString() ? '?' + params.toString() : '');
}

// ── Sync from Meta ────────────────────────────────────────────────────────────
function syncLibrary() {
    const deviceId = document.getElementById('syncDevice').value;
    if (!deviceId) { toastr.warning('{{ __("Select a device first.") }}'); return; }

    const btn = document.getElementById('syncBtn');
    btn.disabled = true;
    document.getElementById('syncProgress').style.display = '';
    document.getElementById('syncMsg').textContent = '{{ __("Fetching from Meta (this may take 10–30s)…") }}';

    fetch(SYNC_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({
            device_id: deviceId,
            category:  document.getElementById('filterCategory').value || null,
            language:  document.getElementById('filterLanguage').value || null,
        })
    })
    .then(r => r.json())
    .then(d => {
        if (d.error) { toastr.error(d.message); }
        else {
            toastr.success(d.message);
            document.getElementById('totalCount').textContent = d.count;
            setTimeout(() => location.reload(), 1000);
        }
    })
    .catch(() => toastr.error('{{ __("Sync failed. Check your connection.") }}'))
    .finally(() => {
        btn.disabled = false;
        document.getElementById('syncProgress').style.display = 'none';
    });
}

// ── Preview & Customize ───────────────────────────────────────────────────────

// Extract unique variable numbers from a mustache-style string, e.g. "Hello [1], your order [2]" → [1,2]
function extractVars(str) {
    if (!str) return [];
    const found = [];
    const re = /\{\{(\d+)\}\}/g;
    let m;
    while ((m = re.exec(str)) !== null) {
        const n = parseInt(m[1], 10);
        if (!found.includes(n)) found.push(n);
    }
    return found.sort((a,b)=>a-b);
}

// Replace mustache-style variable placeholders with the user-supplied value (or a styled placeholder if empty)
function fillVars(str, vals, asHtml) {
    if (!str) return '';
    return str.replace(/\{\{(\d+)\}\}/g, (_, n) => {
        const v = (vals[parseInt(n)] || '').trim();
        if (asHtml) {
            return v
                ? '<span style="background:#fff3cd;border-radius:3px;padding:0 2px">' + escHtml(v) + '</span>'
                : '<span style="background:#ffeeba;border-radius:3px;padding:0 2px;color:#856404">' + '{' + '{' + n + '}}</span>';
        }
        return v || ('{' + '{' + n + '}}');
    });
}

function updateLiveBubble() {
    const t = TPL_DATA[previewActiveId];
    if (!t) return;

    // Collect current variable values from inputs
    const vals = {};
    document.querySelectorAll('#varFields input[data-var]').forEach(inp => {
        vals[parseInt(inp.dataset.var)] = inp.value;
    });

    const headerEl = document.getElementById('liveHeader');
    const bodyEl   = document.getElementById('liveBody');
    const footerEl = document.getElementById('liveFooter');
    const btnsEl   = document.getElementById('liveButtons');

    if (t.header) {
        headerEl.style.display = '';
        headerEl.innerHTML = fillVars(escHtml(t.header), vals, true);
    } else {
        headerEl.style.display = 'none';
    }

    bodyEl.innerHTML = fillVars(escHtml(t.body || ''), vals, true) || '<em style="color:#aaa">{{ __("No body text") }}</em>';

    if (t.footer) {
        footerEl.style.display = '';
        footerEl.textContent = t.footer;
    } else {
        footerEl.style.display = 'none';
    }

    // Timestamp
    const now = new Date();
    document.getElementById('liveTime').innerHTML =
        `${String(now.getHours()).padStart(2,'0')}:${String(now.getMinutes()).padStart(2,'0')} <i class="bi bi-check2-all" style="color:#53bdeb"></i>`;

    // Buttons
    const buttons = t.buttons || [];
    if (buttons.length) {
        let bHtml = '';
        buttons.forEach(b => {
            const icon = b.type === 'URL' ? 'box-arrow-up-right' : b.type === 'PHONE_NUMBER' ? 'telephone' : 'reply';
            bHtml += `<div style="background:#fff;border-radius:8px;padding:7px 10px;text-align:center;margin-top:2px;box-shadow:0 1px 2px rgba(0,0,0,.1)">
                <span style="font-size:13px;color:#00a5f4;font-weight:500">
                    <i class="bi bi-${icon} me-1"></i>${escHtml(b.text || b.type)}
                </span>
            </div>`;
        });
        btnsEl.innerHTML = bHtml;
        btnsEl.style.marginTop = '2px';
    } else {
        btnsEl.innerHTML = '';
    }
}

function previewTemplate(id) {
    const t = TPL_DATA[id];
    if (!t) return;
    previewActiveId = id;

    // Title + badges
    document.getElementById('previewTitle').textContent = t.name;
    const catColor = t.category === 'MARKETING' ? 'warning text-dark' : t.category === 'UTILITY' ? 'info text-dark' : 'success';
    let badges = `<span class="badge bg-${catColor}" style="font-size:10px">${escHtml(t.category)}</span>`;
    badges += ` <span class="badge bg-light text-muted border" style="font-size:10px">${escHtml((t.language||'').toUpperCase())}</span>`;
    if (t.usecase)  badges += ` <span class="badge bg-light text-muted border" style="font-size:10px">${escHtml(t.usecase.replace(/_/g,' '))}</span>`;
    if (t.industry) badges += ` <span class="badge bg-light text-muted border" style="font-size:10px">${escHtml(t.industry.replace(/_/g,' '))}</span>`;
    document.getElementById('previewBadges').innerHTML = badges;

    // Meta info panel
    let metaHtml = '';
    if (t.usecase)  metaHtml += `<div><strong>{{ __("Use Case") }}:</strong> ${escHtml(t.usecase.replace(/_/g,' '))}</div>`;
    if (t.industry) metaHtml += `<div><strong>{{ __("Industry") }}:</strong> ${escHtml(t.industry.replace(/_/g,' '))}</div>`;
    document.getElementById('previewMeta').innerHTML = metaHtml || '<em>{{ __("No additional info.") }}</em>';

    // Build variable input fields
    const allVars = [...new Set([...extractVars(t.header), ...extractVars(t.body)])];
    const varFields = document.getElementById('varFields');
    if (allVars.length === 0) {
        varFields.innerHTML = '<p class="text-muted small">{{ __("No variables in this template.") }}</p>';
    } else {
        const _varLabel   = '{{ __("Variable") }}';
        const _enterLabel = '{{ __("Enter value for") }}';
        let fieldsHtml = '';
        allVars.forEach(n => {
            const varTag = '{' + '{' + n + '}}';
            fieldsHtml += '<div class="mb-2">'
                + '<label class="form-label form-label-sm mb-1">'
                + '<code>' + varTag + '</code> — ' + _varLabel + ' ' + n
                + '</label>'
                + '<input type="text" class="form-control form-control-sm var-input"'
                + ' data-var="' + n + '" placeholder="' + _enterLabel + ' ' + varTag + '">'
                + '</div>';
        });
        varFields.innerHTML = fieldsHtml;

        // Live update on each keystroke
        varFields.querySelectorAll('input').forEach(inp => {
            inp.addEventListener('input', updateLiveBubble);
        });
    }

    // URL button row
    const hasUrl = (t.buttons || []).some(b => b.type === 'URL');
    document.getElementById('urlRow').style.display = hasUrl ? '' : 'none';
    if (hasUrl) {
        const urlBtn = (t.buttons || []).find(b => b.type === 'URL');
        document.getElementById('customUrl').placeholder = urlBtn?.url || 'https://yoursite.com';
        document.getElementById('customUrl').value = '';
    }

    // Initial bubble render
    updateLiveBubble();

    bootstrap.Modal.getOrCreateInstance(document.getElementById('previewModal')).show();
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Add template ──────────────────────────────────────────────────────────────
function addFromPreview() {
    if (previewActiveId === null) return;
    const btn = document.getElementById('previewAddBtn');
    addTemplate(previewActiveId, btn, true);
}

function addTemplate(id, btn, fromPreview = false) {
    const t = TPL_DATA[id];
    if (!t) return;

    const deviceId = document.getElementById('syncDevice').value;
    if (!deviceId) {
        toastr.warning('{{ __("Select a device first (needed to add to your WABA).") }}');
        document.getElementById('syncDevice').focus();
        return;
    }

    // If URL button and not from preview modal, open preview first
    const hasUrl = (t.buttons || []).some(b => b.type === 'URL');
    if (hasUrl && !fromPreview) {
        previewTemplate(id);
        return;
    }

    const customUrl = fromPreview ? (document.getElementById('customUrl').value.trim() || null) : null;

    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    fetch(ADD_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({
            device_id:  deviceId,
            template:   t,
            custom_url: customUrl,
        })
    })
    .then(r => r.json())
    .then(d => {
        if (d.error) {
            toastr.error(d.message);
            btn.disabled = false;
            btn.innerHTML = orig;
        } else {
            toastr.success(d.message);
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>{{ __("Added") }}';
            btn.classList.remove('btn-outline-success');
            btn.classList.add('btn-success');
            if (fromPreview) {
                bootstrap.Modal.getInstance(document.getElementById('previewModal'))?.hide();
            }
        }
    })
    .catch(() => {
        toastr.error('{{ __("Failed to add template.") }}');
        btn.disabled = false;
        btn.innerHTML = orig;
    });
}
</script>
@endpush

</x-layout-dashboard>
