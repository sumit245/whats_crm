<x-layout-dashboard title="{{ __('Create Template') }}">

    <x-page-header title="{{ __('Create HSM Template') }}"
        subtitle="{{ __('Templates must be approved by Meta before use. This usually takes 24–48 hours.') }}"
        :breadcrumb="[__('Templates'), __('Create')]">
        <a href="{{ route('templates.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> {{ __('Back') }}
        </a>
    </x-page-header>

    <div class="card">
        <div class="card-body">
            {{-- SmartWizard --}}
            <div id="smartwizard">
                <ul class="nav">
                    <li class="nav-item">
                        <a class="nav-link" href="#step-1"><h6 class="mb-0">{{ __('Basic Info') }}</h6></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#step-2"><h6 class="mb-0">{{ __('Header') }}</h6></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#step-3"><h6 class="mb-0">{{ __('Body & Buttons') }}</h6></a>
                    </li>
                    <li class="nav-item" id="carousel-step-nav" style="display:none">
                        <a class="nav-link" href="#step-carousel"><h6 class="mb-0">{{ __('Carousel Cards') }}</h6></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#step-4"><h6 class="mb-0">{{ __('Preview & Submit') }}</h6></a>
                    </li>
                </ul>

                <div class="tab-content mt-3">
                        {{-- Step 1: Basic Info --}}
                        <div id="step-1" class="tab-pane" role="tabpanel">
                            <div class="row g-3 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">{{ __('Device') }} <span class="text-danger">*</span></label>
                                    <select id="deviceId" class="form-select" required>
                                        <option value="">{{ __('Select connected device') }}</option>
                                        @foreach ($devices as $device)
                                            <option value="{{ $device->id }}">{{ $device->meta_profile['verified_name'] ?? $device->body }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">{{ __('Template Name') }} <span class="text-danger">*</span></label>
                                    <input type="text" id="templateName" class="form-control" placeholder="order_confirmation" pattern="[a-z0-9_]+" required>
                                    <div class="form-text">{{ __('Lowercase letters, numbers, underscores only. E.g. order_confirmation') }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">{{ __('Category') }} <span class="text-danger">*</span></label>
                                    <div class="d-flex gap-3 mt-1">
                                        @foreach (['MARKETING' => ['primary','bi-megaphone'], 'UTILITY' => ['info','bi-gear'], 'AUTHENTICATION' => ['warning','bi-shield-lock']] as $cat => [$color, $icon])
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="category" id="cat{{ $cat }}" value="{{ $cat }}" {{ $cat === 'MARKETING' ? 'checked' : '' }}>
                                                <label class="form-check-label" for="cat{{ $cat }}">
                                                    <span class="badge bg-{{ $color }}"><i class="bi {{ $icon }} me-1"></i>{{ $cat }}</span>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">{{ __('Language') }} <span class="text-danger">*</span></label>
                                    <select id="templateLanguage" class="form-select">
                                        @foreach ([
                                            'en' => 'English', 'en_US' => 'English (US)', 'en_GB' => 'English (UK)',
                                            'ar' => 'Arabic', 'es' => 'Spanish', 'fr' => 'French',
                                            'de' => 'German', 'pt_BR' => 'Portuguese (BR)', 'hi' => 'Hindi',
                                            'id' => 'Indonesian', 'ms' => 'Malay', 'tr' => 'Turkish',
                                        ] as $code => $label)
                                            <option value="{{ $code }}">{{ $label }} ({{ $code }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <div class="form-check form-switch mt-1">
                                        <input class="form-check-input" type="checkbox" id="isCarousel" role="switch">
                                        <label class="form-check-label" for="isCarousel">
                                            <strong>{{ __('Carousel Template') }}</strong>
                                            <span class="text-muted ms-1">{{ __('(Multiple cards with images & buttons)') }}</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Step 2: Header --}}
                        <div id="step-2" class="tab-pane" role="tabpanel">
                            <div class="mt-3">
                                <label class="form-label fw-semibold">{{ __('Header') }} <small class="text-muted fw-normal">({{ __('optional') }})</small></label>
                                <div class="d-flex gap-2 mb-3 flex-wrap">
                                    @foreach (['NONE' => 'None', 'TEXT' => 'Text', 'IMAGE' => 'Image URL', 'VIDEO' => 'Video URL', 'DOCUMENT' => 'Document URL'] as $fmt => $label)
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="headerFormat" id="hf{{ $fmt }}" value="{{ $fmt }}" {{ $fmt === 'NONE' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="hf{{ $fmt }}">{{ $label }}</label>
                                        </div>
                                    @endforeach
                                </div>
                                <div id="headerTextInput" class="d-none">
                                    <input type="text" id="headerText" class="form-control" placeholder="{{ __('Header text (max 60 chars)') }}" maxlength="60">
                                    <div class="form-text">{{ __('You can use one variable:') }} <code>@{{1}}</code></div>
                                </div>
                                <div id="headerMediaInput" class="d-none">
                                    <input type="url" id="headerMediaUrl" class="form-control" placeholder="https://example.com/image.jpg">
                                    <div class="form-text">{{ __('Public URL to the media file. Must be accessible by Meta.') }}</div>
                                </div>
                            </div>
                        </div>

                        {{-- Step 3: Body & Buttons --}}
                        <div id="step-3" class="tab-pane" role="tabpanel">
                            <div class="row g-3 mt-2">
                                <div class="col-12">
                                    <label class="form-label fw-semibold">{{ __('Body') }} <span class="text-danger">*</span></label>
                                    <textarea id="bodyText" class="form-control" rows="5" maxlength="1024"
                                        placeholder="{{ __('Enter your message. Use') }} @{{1}}, @{{2}} {{ __('for variables.') }}" required></textarea>
                                    <div class="d-flex justify-content-between">
                                        <div class="form-text">{{ __('Use') }} <code>@{{1}}</code>, <code>@{{2}}</code> {{ __('for personalization variables.') }}</div>
                                        <small class="text-muted" id="bodyCount">0/1024</small>
                                    </div>
                                    <div id="variableHints" class="mt-2"></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">{{ __('Footer') }} <small class="text-muted fw-normal">({{ __('optional') }})</small></label>
                                    <input type="text" id="footerText" class="form-control" placeholder="{{ __('Footer text (max 60 chars)') }}" maxlength="60">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">{{ __('Buttons') }} <small class="text-muted fw-normal">({{ __('optional, max 10') }})</small></label>
                                    <div id="buttonsList"></div>
                                    <button type="button" id="addButtonBtn" class="btn btn-sm btn-outline-primary mt-2">
                                        <i class="bi bi-plus"></i> {{ __('Add Button') }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Step Carousel: Carousel Cards (shown only when isCarousel = true) --}}
                        <div id="step-carousel" class="tab-pane" role="tabpanel">
                            <div class="mt-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0">{{ __('Carousel Cards') }} <span class="badge bg-secondary" id="card-count-badge">0/10</span></h6>
                                    <button type="button" id="addCardBtn" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-plus"></i> {{ __('Add Card') }}
                                    </button>
                                </div>
                                <div id="carouselCards"></div>
                            </div>
                        </div>

                        {{-- Step 4: Preview & Submit --}}
                        <div id="step-4" class="tab-pane" role="tabpanel">
                            <div class="row g-3 mt-2">
                                <div class="col-md-5">
                                    <h6>{{ __('Preview') }}</h6>
                                    <div class="border rounded p-3 mpwa-preview-panel" id="templatePreviewCard">
                                        <div class="text-muted text-center py-3">{{ __('Fill in the form to see a preview') }}</div>
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <h6>{{ __('Components JSON (sent to Meta)') }}</h6>
                                    <pre id="componentsJsonDisplay" class="border rounded p-2 mpwa-code-panel small"></pre>
                                </div>
                            </div>
                        </div>

                </div>{{-- end tab content --}}
            </div>
        </div>
    </div>

<script>
$(function () {

    // ── SmartWizard init ──────────────────────────────────────────────
    $('#smartwizard').smartWizard({
        selected: 0,
        theme: 'dots',
        transition: { animation: 'none' },
        toolbarSettings: {
            toolbarPosition: 'bottom',
            toolbarExtraButtons: [
                $('<button type="button" class="btn btn-primary">{{ __("Submit Template") }}</button>').on('click', submitTemplate),
            ],
        },
    });

    // ── Header format toggle ──────────────────────────────────────────
    $('input[name=headerFormat]').on('change', function () {
        const v = $(this).val();
        $('#headerTextInput').toggleClass('d-none', v !== 'TEXT');
        $('#headerMediaInput').toggleClass('d-none', !['IMAGE','VIDEO','DOCUMENT'].includes(v));
    });

    // ── Body variable hints ───────────────────────────────────────────
    $('#bodyText').on('input', function () {
        const text = $(this).val();
        $('#bodyCount').text(text.length + '/1024');
        const vars = [...text.matchAll(/\{\{(\d+)\}\}/g)].map(m => m[1]);
        const unique = [...new Set(vars)].sort((a,b) => +a - +b);
        let html = '';
        unique.forEach(v => {
            // Build the literal placeholder token in JS so Blade never tries to parse it.
            const token = '{' + '{' + v + '}' + '}';
            html += '<div class="input-group input-group-sm mb-1">'
                  + '<span class="input-group-text">' + token + '</span>'
                  + '<input type="text" class="form-control" placeholder="Example value for ' + token + '" id="varExample_' + v + '">'
                  + '</div>';
        });
        $('#variableHints').html(html ? `<div class="alert alert-info py-2"><small><strong>{{ __("Variable examples") }}:</strong></small><div class="mt-1">${html}</div></div>` : '');
    });

    // ── Buttons ───────────────────────────────────────────────────────
    let buttonCount = 0;
    $('#addButtonBtn').on('click', function () {
        if (buttonCount >= 10) { toastr.warning('{{ __("Maximum 10 buttons") }}'); return; }
        buttonCount++;
        const i = buttonCount;
        const html = `<div class="border rounded p-2 mb-2 button-row" id="btnRow_${i}">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <select class="form-select form-select-sm btn-type" data-index="${i}">
                        <option value="QUICK_REPLY">Quick Reply</option>
                        <option value="URL">URL</option>
                        <option value="PHONE_NUMBER">Phone</option>
                        <option value="COPY_CODE">Copy Code (OTP)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control form-control-sm btn-text" placeholder="{{ __('Button label') }}" maxlength="25">
                </div>
                <div class="col-md-4 btn-value-col">
                    <input type="text" class="form-control form-control-sm btn-value" placeholder="{{ __('URL or phone number') }}">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-btn" data-index="${i}"><i class="bi bi-x"></i></button>
                </div>
            </div>
        </div>`;
        $('#buttonsList').append(html);
        $(`.btn-type[data-index=${i}]`).on('change', function () {
            const v = $(this).val();
            $(`#btnRow_${i} .btn-value-col`).toggleClass('d-none', v === 'QUICK_REPLY' || v === 'COPY_CODE');
        });
        $(`.remove-btn[data-index=${i}]`).on('click', function () { $(`#btnRow_${i}`).remove(); });
    });

    // ── Carousel toggle ───────────────────────────────────────────────
    $('#isCarousel').on('change', function () {
        const on = $(this).is(':checked');
        $('#carousel-step-nav').toggle(on);
        if (on) {
            // Disable header/footer/buttons steps when carousel is active
            $('#headerTextInput, #headerMediaInput').addClass('d-none');
            $('input[name=headerFormat][value=NONE]').prop('checked', true);
        }
    });

    // ── Carousel card builder ─────────────────────────────────────────
    let cardCount = 0;

    $('#addCardBtn').on('click', function () {
        if (cardCount >= 10) { toastr.warning('{{ __("Maximum 10 cards") }}'); return; }
        cardCount++;
        const c = cardCount;
        const html = `<div class="card mb-3 carousel-card" id="card-${c}">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <span class="fw-semibold">{{ __('Card') }} ${c}</span>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeCard(${c})"><i class="bi bi-x"></i></button>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <label class="form-label small fw-semibold">{{ __('Card Image URL') }} <span class="text-danger">*</span></label>
                    <input type="url" class="form-control form-control-sm card-image" id="card-img-${c}" placeholder="https://...">
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold">{{ __('Card Body') }} <span class="text-danger">*</span></label>
                    <textarea class="form-control form-control-sm card-body-text" id="card-body-${c}" rows="2" maxlength="160" placeholder="{{ __('Card message text') }}"></textarea>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold">{{ __('Buttons') }} <span class="text-muted fw-normal">({{ __('max 2') }})</span></label>
                    <div id="card-btns-${c}"></div>
                    <button type="button" class="btn btn-xs btn-outline-secondary mt-1" onclick="addCardButton(${c})">
                        <i class="bi bi-plus"></i> {{ __('Add Button') }}
                    </button>
                </div>
            </div>
        </div>`;
        $('#carouselCards').append(html);
        updateCardCount();
    });

    window.removeCard = function (c) {
        $(`#card-${c}`).remove();
        cardCount = Math.max(0, cardCount - 1);
        updateCardCount();
    };

    function updateCardCount() {
        const n = $('#carouselCards .carousel-card').length;
        $('#card-count-badge').text(n + '/10');
    }

    let cardBtnCounts = {};
    window.addCardButton = function (c) {
        cardBtnCounts[c] = (cardBtnCounts[c] || 0) + 1;
        if (cardBtnCounts[c] > 2) { toastr.warning('{{ __("Max 2 buttons per card") }}'); cardBtnCounts[c] = 2; return; }
        const b = cardBtnCounts[c];
        const html = `<div class="input-group input-group-sm mb-1 card-btn-row" id="card-${c}-btn-${b}">
            <select class="form-select form-select-sm card-btn-type" style="max-width:130px">
                <option value="QUICK_REPLY">Quick Reply</option>
                <option value="URL">URL</option>
                <option value="PHONE_NUMBER">Phone</option>
            </select>
            <input type="text" class="form-control card-btn-text" placeholder="{{ __('Label') }}" maxlength="25">
            <input type="text" class="form-control card-btn-value" placeholder="{{ __('URL or phone') }}">
            <button type="button" class="btn btn-outline-danger" onclick="$(this).closest('.card-btn-row').remove()"><i class="bi bi-x"></i></button>
        </div>`;
        $(`#card-btns-${c}`).append(html);
    };

    function buildCarouselCards() {
        const cards = [];
        $('#carouselCards .carousel-card').each(function () {
            const id     = $(this).attr('id').replace('card-', '');
            const imgUrl = $(`#card-img-${id}`).val().trim();
            const body   = $(`#card-body-${id}`).val().trim();
            if (!imgUrl || !body) return;

            const components = [
                { type: 'HEADER', format: 'IMAGE', example: { header_handle: [imgUrl] } },
                { type: 'BODY',   text: body },
            ];

            const buttons = [];
            $(`#card-btns-${id} .card-btn-row`).each(function () {
                const type  = $(this).find('.card-btn-type').val();
                const text  = $(this).find('.card-btn-text').val();
                const value = $(this).find('.card-btn-value').val();
                if (!text) return;
                const btn = { type, text };
                if (type === 'URL') btn.url = value;
                if (type === 'PHONE_NUMBER') btn.phone_number = value;
                buttons.push(btn);
            });
            if (buttons.length) components.push({ type: 'BUTTONS', buttons });

            cards.push({ components });
        });
        return cards;
    }

    // ── Build components JSON ─────────────────────────────────────────
    function buildComponents() {
        // If carousel mode, build carousel component instead of standard layout
        if ($('#isCarousel').is(':checked')) {
            const cards = buildCarouselCards();
            if (!cards.length) return [];
            const bodyTxt = $('#bodyText').val();
            const comps = [];
            if (bodyTxt) comps.push({ type: 'BODY', text: bodyTxt });
            comps.push({ type: 'CAROUSEL', cards });
            return comps;
        }

        const components = [];

        // Header
        const hFormat = $('input[name=headerFormat]:checked').val();
        if (hFormat && hFormat !== 'NONE') {
            const hComp = { type: 'HEADER', format: hFormat };
            if (hFormat === 'TEXT') hComp.text = $('#headerText').val();
            else hComp.example = { header_handle: [$('#headerMediaUrl').val()] };
            components.push(hComp);
        }

        // Body
        const bodyTxt = $('#bodyText').val();
        if (bodyTxt) {
            const bComp = { type: 'BODY', text: bodyTxt };
            const vars = [...bodyTxt.matchAll(/\{\{(\d+)\}\}/g)].map(m => m[1]);
            const unique = [...new Set(vars)].sort((a,b) => +a - +b);
            if (unique.length > 0) {
                bComp.example = { body_text: [unique.map(v => $(`#varExample_${v}`).val() || `value${v}`)] };
            }
            components.push(bComp);
        }

        // Footer
        const footerTxt = $('#footerText').val();
        if (footerTxt) components.push({ type: 'FOOTER', text: footerTxt });

        // Buttons
        const buttons = [];
        $('.button-row').each(function () {
            const type = $(this).find('.btn-type').val();
            const text = $(this).find('.btn-text').val();
            const value = $(this).find('.btn-value').val();
            if (!text) return;
            const btn = { type, text };
            if (type === 'URL') btn.url = value;
            if (type === 'PHONE_NUMBER') btn.phone_number = value;
            buttons.push(btn);
        });
        if (buttons.length > 0) components.push({ type: 'BUTTONS', buttons });

        return components;
    }

    // ── Live preview on step 4 ────────────────────────────────────────
    $('#smartwizard').on('showStep', function (e, anchorObject, stepNumber, stepDirection, stepPosition) {
        if (stepPosition !== 'last') return;
        const comps = buildComponents();
        $('#componentsJsonDisplay').text(JSON.stringify(comps, null, 2));

        let html = '<div class="wa-bubble">';
        comps.forEach(c => {
            if (c.type === 'HEADER' && c.text) html += '<div class="fw-bold mb-1">' + $('<div>').text(c.text).html() + '</div>';
            if (c.type === 'HEADER' && c.format && c.format !== 'TEXT') html += '<div class="mpwa-media-placeholder mb-1"><i class="bi bi-image fs-3"></i></div>';
            if (c.type === 'BODY') html += '<div class="mb-1">' + $('<div>').text(c.text).html().replace(/\n/g, '<br>') + '</div>';
            if (c.type === 'FOOTER') html += '<div class="small text-muted mt-1">' + $('<div>').text(c.text).html() + '</div>';
            if (c.type === 'CAROUSEL') {
                html += '<div class="d-flex overflow-auto gap-2 mt-2">';
                (c.cards || []).forEach((card, i) => {
                    html += '<div class="border rounded p-2" style="min-width:160px">';
                    html += '<div class="mpwa-media-placeholder mb-1" style="height:80px"><i class="bi bi-image fs-3"></i></div>';
                    const cardBody = card.components?.find(x => x.type === 'BODY');
                    if (cardBody) html += '<div class="small mb-1">' + $('<div>').text(cardBody.text).html() + '</div>';
                    const cardBtns = card.components?.find(x => x.type === 'BUTTONS');
                    if (cardBtns) cardBtns.buttons.forEach(b => {
                        html += '<div class="text-primary text-center small border-top py-1">' + $('<div>').text(b.text).html() + '</div>';
                    });
                    html += '</div>';
                });
                html += '</div>';
            }
        });
        const btns = comps.find(c => c.type === 'BUTTONS');
        if (btns) {
            html += '<div class="border-top mt-2 pt-2">';
            btns.buttons.forEach(b => {
                html += '<div class="text-primary text-center py-1 border-bottom">' + $('<div>').text(b.text).html() + '</div>';
            });
            html += '</div>';
        }
        html += '</div>';
        $('#templatePreviewCard').html(html);
    });

    // ── Submit ────────────────────────────────────────────────────────
    function submitTemplate() {
        const deviceId = $('#deviceId').val();
        const name = $('#templateName').val().trim();
        const category = $('input[name=category]:checked').val();
        const language = $('#templateLanguage').val();
        const bodyText = $('#bodyText').val().trim();

        if (!deviceId) { toastr.error('{{ __("Please select a device") }}'); return; }
        if (!name || !/^[a-z0-9_]+$/.test(name)) { toastr.error('{{ __("Invalid template name. Use only lowercase letters, numbers, underscores.") }}'); return; }
        if (!bodyText) { toastr.error('{{ __("Body text is required") }}'); return; }

        const components = buildComponents();
        if (!components.find(c => c.type === 'BODY')) { toastr.error('{{ __("Body is required") }}'); return; }

        $.ajax({
            method: 'POST',
            url: '{{ route("templates.store") }}',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: {
                device_id: deviceId,
                name: name,
                category: category,
                language: language,
                components: JSON.stringify(components),
            },
            success: (res) => {
                toastr.success(res.message);
                setTimeout(() => window.location = '{{ route("templates.index") }}', 1500);
            },
            error: (err) => {
                toastr.error(err.responseJSON?.message ?? '{{ __("Failed to submit template") }}');
            },
        });
    }
});
</script>

</x-layout-dashboard>
