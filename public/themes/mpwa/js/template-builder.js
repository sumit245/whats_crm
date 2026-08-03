/**
 * WhatsApp Template Builder — aligned with Meta Business Messaging docs:
 * https://developers.facebook.com/documentation/business-messaging/whatsapp/templates/overview
 */
(function ($, window) {
    'use strict';

    const CFG = window.TEMPLATE_BUILDER || {};

    /** @see Meta WhatsApp Manager “Set up template” options per category */
    const TEMPLATE_TYPES = {
        MARKETING: [
            { id: 'default', title: 'Default', desc: 'Send messages with media and customised buttons to engage your customers.' },
            { id: 'catalog', title: 'Catalogue', desc: 'Send messages that drive sales by connecting your product catalogue.' },
            { id: 'flow', title: 'Flows', desc: 'Send a form to capture customer interests, appointment requests or run surveys.' },
            { id: 'order_details', title: 'Order details', desc: 'Send messages through which customers can pay you.' },
            { id: 'call_permission', title: 'Calling permissions request', desc: 'Ask customers if you can call them on WhatsApp.' },
            { id: 'carousel', title: 'Media card carousel', desc: 'Up to 10 scrollable cards with images/videos and buttons (marketing only).' },
        ],
        UTILITY: [
            { id: 'default', title: 'Default', desc: 'Follow up on user actions or requests — order updates, alerts, reminders.' },
            { id: 'flow', title: 'Flows', desc: 'Collect structured information with a WhatsApp Flow.' },
            { id: 'order_details', title: 'Order details', desc: 'Send order or payment details to customers.' },
            { id: 'call_permission', title: 'Calling permissions request', desc: 'Request permission to call the customer on WhatsApp.' },
        ],
        AUTHENTICATION: [
            { id: 'otp_copy', title: 'Copy code', desc: 'One-time password with a copy-code button (Meta authentication format).' },
            { id: 'otp_autofill', title: 'One-tap autofill', desc: 'OTP with one-tap autofill button (requires app signing setup in Meta).' },
        ],
    };

    /** Contextual preview + guidance (Meta right panel on step 1) */
    const TYPE_GUIDANCE = {
        default: {
            goodFor: ['Promotions and offers', 'Account updates', 'General marketing'],
            customize: ['Header', 'Body', 'Footer', 'Buttons'],
            preview: { media: false, cta: null },
        },
        catalog: {
            goodFor: ['Product or service discovery', 'Sales and offers', 'Retargeting customers'],
            customize: ['Connect a catalogue', 'Body'],
            preview: {
                media: 'catalog',
                header: "View Jasper's Market's Catalog on WhatsApp",
                body: 'Browse our latest products and bestsellers. #happyshopping',
                cta: 'View catalog',
                ctaIcon: 'bi-bag',
            },
        },
        flow: {
            goodFor: ['Lead capture', 'Appointments', 'Surveys and feedback'],
            customize: ['Body', 'Flow button', 'Header (optional)'],
            preview: { body: 'Hello', cta: 'View Flow', ctaIcon: 'bi-list-ul' },
        },
        order_details: {
            goodFor: ['Payment requests', 'Order confirmations', 'Checkout flows'],
            customize: ['Order details component', 'Body'],
            preview: { body: 'Your order is ready. Tap below to view payment details.', cta: 'Review order', ctaIcon: 'bi-receipt' },
        },
        call_permission: {
            goodFor: ['Requesting call permission', 'Support callbacks'],
            customize: ['Body', 'Call permission request'],
            preview: { body: 'We would like to call you on WhatsApp about your recent inquiry.', cta: 'Allow calls', ctaIcon: 'bi-telephone' },
        },
        carousel: {
            goodFor: ['Product showcases', 'Multi-offer campaigns', 'Visual storytelling'],
            customize: ['Intro body', 'Carousel cards (2–10)', 'Per-card buttons'],
            preview: { carousel: true },
        },
        otp_copy: {
            goodFor: ['Login verification', 'Transaction confirmation', 'Account security'],
            customize: ['OTP body with {{1}}', 'Copy code button'],
            preview: { body: '{{1}} is your verification code.', cta: 'Copy code', ctaIcon: 'bi-clipboard' },
        },
        otp_autofill: {
            goodFor: ['Frictionless login on mobile', 'App verification'],
            customize: ['OTP body', 'One-tap autofill button'],
            preview: { body: '{{1}} is your verification code.', cta: 'Autofill', ctaIcon: 'bi-shield-check' },
        },
    };

    const CTA_OPTIONS = [
        { type: 'URL', label: 'Visit website', sub: 'Max 2 per template', max: 2 },
        { type: 'PHONE_NUMBER', label: 'Call phone number', sub: '1 button maximum', max: 1 },
        { type: 'VOICE_CALL', label: 'Call on WhatsApp', sub: 'Voice call button', max: 1 },
        { type: 'COPY_CODE', label: 'Copy offer code', sub: '1 button maximum', max: 1 },
        { type: 'FLOW', label: 'Complete flow', sub: '1 button maximum', max: 1 },
        { type: 'QUICK_REPLY', label: 'Quick reply', sub: 'Up to 10', max: 10 },
    ];

    const state = {
        step: 0,
        category: 'MARKETING',
        templateType: 'default',
        variableFormat: 'positional',
        buttonCount: 0,
        cardCount: 0,
        cardBtnCounts: {},
    };

    if (!$('#templateBuilder').length) return;

    function guidance() {
        return TYPE_GUIDANCE[state.templateType] || TYPE_GUIDANCE.default;
    }

    // ── Step navigation ───────────────────────────────────────────────
    function goToStep(index) {
        if (index < 0 || index > 2) return;
        if (index >= 1 && !validateSetup()) return;
        if (index >= 2 && !validateEditor()) return;

        state.step = index;
        $('.wa-tpl-pane').removeClass('is-active');
        $(`.wa-tpl-pane[data-step="${index}"]`).addClass('is-active');
        $('.wa-tpl__split').toggleClass('wa-tpl__split--review', index === 2);

        $('.wa-tpl__step').each(function (i) {
            $(this).toggleClass('is-active', i === index).toggleClass('is-done', i < index);
        });

        $('#waBtnPrev').toggle(index > 0);
        $('#waBtnNext').toggle(index < 2);
        $('#waBtnSubmit').toggle(index === 2);

        if (index === 0) renderSetupPreview();
        if (index === 1) {
            updateSummaryChip();
            refreshLivePreview();
        }
        if (index === 2) renderReview();

        window.location.hash = 'step-' + (index + 1);
    }

    function validateSetup() {
        return true;
    }

    function validateEditor() {
        if (!$('#waDevice').val()) {
            toastr.error(CFG.msg?.selectDevice || 'Select a WhatsApp account');
            return false;
        }
        const name = $('#waName').val().trim();
        if (!name || !/^[a-z0-9_]+$/.test(name)) {
            toastr.error(CFG.msg?.invalidName || 'Invalid template name');
            return false;
        }
        if (state.category === 'AUTHENTICATION') {
            return validateAuthBody();
        }
        if (state.templateType === 'carousel') {
            if (!buildCarouselCards().length) {
                toastr.error(CFG.msg?.carouselRequired || 'Add at least 2 carousel cards');
                return false;
            }
            return true;
        }
        if (!$('#waBody').val().trim()) {
            toastr.error(CFG.msg?.bodyRequired || 'Body is required');
            return false;
        }
        if (!validateVariableSamples()) return false;
        return true;
    }

    function validateAuthBody() {
        const body = $('#waBody').val();
        if (!/\{\{1\}\}/.test(body) && !/\{\{code\}\}/i.test(body)) {
            toastr.error(CFG.msg?.authBody || 'Authentication body must include {{1}} for the OTP');
            return false;
        }
        return true;
    }

    function validateVariableSamples() {
        let ok = true;
        $('.wa-var-field').each(function () {
            const $f = $(this);
            const val = $f.find('input').val().trim();
            if (!val) {
                $f.addClass('is-invalid');
                ok = false;
            } else {
                $f.removeClass('is-invalid');
            }
        });
        if (!ok) toastr.error(CFG.msg?.varRequired || 'Add sample text for all variables (required by Meta)');
        return ok;
    }

    // ── Category tabs & types ─────────────────────────────────────────
    function renderTypeList() {
        const types = TEMPLATE_TYPES[state.category] || [];
        if (!types.find(t => t.id === state.templateType)) {
            state.templateType = types[0]?.id || 'default';
        }
        let html = '';
        types.forEach(t => {
            const sel = t.id === state.templateType;
            html += `<label class="wa-type-item ${sel ? 'is-selected' : ''}" data-type="${t.id}">
                <input type="radio" name="waTemplateType" value="${t.id}" ${sel ? 'checked' : ''}>
                <div>
                    <p class="wa-type-item__title">${escapeHtml(t.title)}</p>
                    <p class="wa-type-item__desc">${escapeHtml(t.desc)}</p>
                </div>
            </label>`;
        });
        $('#waTypeList').html(html);
        applyTypePreset();
        renderSetupPreview();
    }

    function applyTypePreset() {
        const auth = state.category === 'AUTHENTICATION';
        const carousel = state.templateType === 'carousel';
        const flow = state.templateType === 'flow';

        $('#waAuthNotice').toggleClass('d-none', !auth);
        $('#waCarouselBlock').toggleClass('d-none', !carousel);
        $('#waButtonsBlock, #waHeaderBlock, #waFooterBlock').toggleClass('d-none', carousel || auth);
        $('#waCtaBlock').toggleClass('d-none', auth || carousel);
        $('#waVarFormatRow').toggleClass('d-none', auth);

        if (auth && state.templateType === 'otp_copy') {
            if (!$('#waBody').val().trim()) {
                $('#waBody').val('{{1}} is your verification code. For your security, do not share this code.');
            }
            if (!$('.wa-btn-block').length) addButton({ type: 'COPY_CODE', text: 'Copy code', value: '123456' });
        }
        if (auth && state.templateType === 'otp_autofill') {
            if (!$('#waBody').val().trim()) {
                $('#waBody').val('{{1}} is your verification code.');
            }
            $('.wa-btn-block').remove();
            state.buttonCount = 0;
        }
        if (flow && !$('.wa-btn-block').length) {
            addButton({ type: 'FLOW', text: 'View flow', value: '' });
        }

        renderSetupMeta();
        refreshLivePreview();
    }

    $(document).on('click', '.wa-cat-tab', function () {
        $('.wa-cat-tab').removeClass('is-active');
        $(this).addClass('is-active');
        state.category = $(this).data('category');
        $('input[name=waCategory]').prop('checked', false);
        $(`input[name=waCategory][value="${state.category}"]`).prop('checked', true);
        if (state.category === 'AUTHENTICATION') {
            state.templateType = 'otp_copy';
        } else if (state.templateType.startsWith('otp_')) {
            state.templateType = 'default';
        }
        renderTypeList();
    });

    $(document).on('click', '.wa-type-item', function (e) {
        if ($(e.target).is('input')) return;
        state.templateType = $(this).data('type');
        $('.wa-type-item').removeClass('is-selected');
        $(this).addClass('is-selected').find('input').prop('checked', true);
        applyTypePreset();
        renderSetupPreview();
    });

    $(document).on('change', 'input[name=waTemplateType]', function () {
        state.templateType = $(this).val();
        $('.wa-type-item').removeClass('is-selected');
        $(this).closest('.wa-type-item').addClass('is-selected');
        applyTypePreset();
        renderSetupPreview();
    });

    // ── Step 1 contextual preview (Meta right panel) ──────────────────
    function renderSetupPreview() {
        const g = TYPE_GUIDANCE[state.templateType] || TYPE_GUIDANCE.default;
        const p = g.preview || {};

        let bubble = '';
        if (p.carousel) {
            bubble = `<div class="wa-preview-bubble">
                <div class="wa-preview-bubble__body">Intro message for your carousel…</div>
                <div class="d-flex gap-1 p-2 overflow-auto">
                    <div class="border rounded flex-shrink-0" style="width:120px"><div class="wa-preview-bubble__media" style="min-height:70px"></div><div class="p-1 small">Card 1</div></div>
                    <div class="border rounded flex-shrink-0" style="width:120px"><div class="wa-preview-bubble__media" style="min-height:70px"></div><div class="p-1 small">Card 2</div></div>
                </div>
            </div>`;
        } else if (p.media === 'catalog') {
            bubble = `<div class="wa-preview-bubble">
                <div class="wa-preview-bubble__media"><img src="${CFG.catalogPreviewImg || ''}" alt="" onerror="this.style.display='none'"></div>
                <div class="wa-preview-bubble__body"><strong>${escapeHtml(p.header || '')}</strong><br>${escapeHtml(p.body || '')}</div>
                ${p.cta ? `<div class="wa-preview-bubble__cta"><i class="bi ${p.ctaIcon}"></i> ${escapeHtml(p.cta)}</div>` : ''}
            </div>`;
        } else {
            bubble = `<div class="wa-preview-bubble">
                ${p.media ? '<div class="wa-preview-bubble__media"><i class="bi bi-image fs-2 opacity-50"></i></div>' : ''}
                <div class="wa-preview-bubble__body">${escapeHtml(p.body || 'Your message preview')}</div>
                ${p.cta ? `<div class="wa-preview-bubble__cta"><i class="bi ${p.ctaIcon || 'bi-link'}"></i> ${escapeHtml(p.cta)}</div>` : ''}
            </div>`;
        }

        $('#waSetupPreview').html(bubble);
        $('#waGoodFor').html(g.goodFor.map(x => `<li>${escapeHtml(x)}</li>`).join(''));
        $('#waCustomize').html(g.customize.map(x => `<li>${escapeHtml(x)}</li>`).join(''));
    }

    function renderSetupMeta() {
        const catLabel = { MARKETING: 'Marketing', UTILITY: 'Utility', AUTHENTICATION: 'Authentication' }[state.category];
        const typeObj = (TEMPLATE_TYPES[state.category] || []).find(t => t.id === state.templateType);
        $('#waSetupTypeLabel').text(typeObj ? typeObj.title : state.templateType);
        $('#waSetupCatLabel').text(catLabel);
    }

    // ── Header / media sample ─────────────────────────────────────────
    $(document).on('click', '.wa-hdr-pill', function () {
        $('.wa-hdr-pill').removeClass('active');
        $(this).addClass('active');
        const v = $(this).data('format');
        $(`input[name=waHeaderFormat][value="${v}"]`).prop('checked', true);
        $('#waHeaderTextRow').toggleClass('d-none', v !== 'TEXT');
        $('#waHeaderMediaRow').toggleClass('d-none', !['IMAGE', 'VIDEO', 'DOCUMENT'].includes(v));
        $('#waMediaSampleRow').toggleClass('d-none', v === 'NONE' || v === 'TEXT');
        refreshLivePreview();
    });

    $('#waMediaSample').on('change', function () {
        const map = { image: 'IMAGE', video: 'VIDEO', document: 'DOCUMENT' };
        const fmt = map[$(this).val()];
        if (fmt) {
            $(`input[name=waHeaderFormat][value="${fmt}"]`).prop('checked', true);
            $('.wa-hdr-pill').removeClass('active');
            $(`.wa-hdr-pill[data-format="${fmt}"]`).addClass('active');
            $('#waHeaderTextRow').addClass('d-none');
            $('#waHeaderMediaRow').removeClass('d-none');
            $('#waMediaSampleRow').removeClass('d-none');
        }
        refreshLivePreview();
    });
    $('#waVarFormat').on('change', function () {
        state.variableFormat = $(this).val();
        renderVariableSamples();
        refreshLivePreview();
    });

    // ── Body toolbar ──────────────────────────────────────────────────
    function wrapSelection(before, after) {
        const el = document.getElementById('waBody');
        const s = el.selectionStart, e = el.selectionEnd;
        const t = el.value;
        el.value = t.slice(0, s) + before + t.slice(s, e) + after + t.slice(e);
        el.focus();
        $(el).trigger('input');
    }

    $('#waFmtBold').on('click', () => wrapSelection('*', '*'));
    $('#waFmtItalic').on('click', () => wrapSelection('_', '_'));
    $('#waFmtStrike').on('click', () => wrapSelection('~', '~'));
    $('#waFmtCode').on('click', () => wrapSelection('```', '```'));
    $('#waFmtVar').on('click', addNextVariable);

    function addNextVariable(targetId) {
        const el = document.getElementById(targetId || 'waBody');
        const text = el.value;
        if (state.variableFormat === 'named') {
            const n = prompt(CFG.msg?.varNamePrompt || 'Variable name (e.g. first_name):', 'first_name');
            if (!n || !/^[a-z_][a-z0-9_]*$/i.test(n)) return;
            insertAtCursor(el, '{{' + n + '}}');
        } else {
            const nums = [...text.matchAll(/\{\{(\d+)\}\}/g)].map(m => +m[1]);
            const next = nums.length ? Math.max(...nums) + 1 : 1;
            insertAtCursor(el, '{{' + next + '}}');
        }
        $(el).trigger('input');
    }

    function insertAtCursor(el, str) {
        const s = el.selectionStart;
        el.value = el.value.slice(0, s) + str + el.value.slice(s);
        el.selectionStart = el.selectionEnd = s + str.length;
    }

    $('#waHdrAddVar').on('click', () => addNextVariable('waHeaderText'));

    $('#waBody, #waFooter, #waHeaderText, #waHeaderMediaUrl').on('input', function () {
        if (this.id === 'waBody') {
            $('#waBodyCount').text(this.value.length + '/1024');
            renderVariableSamples();
        }
        if (this.id === 'waFooter') $('#waFooterCount').text(this.value.length + '/60');
        refreshLivePreview();
    });

    function extractVariables(text) {
        if (state.variableFormat === 'named') {
            return [...new Set([...text.matchAll(/\{\{([a-z_][a-z0-9_]*)\}\}/gi)].map(m => m[1]))];
        }
        return [...new Set([...text.matchAll(/\{\{(\d+)\}\}/g)].map(m => m[1]))].sort((a, b) => +a - +b);
    }

    function renderVariableSamples() {
        const vars = [
            ...extractVariables($('#waBody').val()),
            ...extractVariables($('#waHeaderText').val() || ''),
        ];
        if (!vars.length) {
            $('#waVarBlock').addClass('d-none');
            return;
        }
        const isNamed = state.variableFormat === 'named';
        let html = '';
        vars.forEach(v => {
            const token = isNamed ? '{{' + v + '}}' : '{{' + v + '}}';
            html += `<div class="wa-var-field" data-var="${escapeAttr(v)}">
                <label>${escapeHtml(token)}</label>
                <input type="text" class="form-control wa-var-sample" data-var="${escapeAttr(v)}" placeholder="${escapeAttr(CFG.msg?.varPlaceholder || 'Enter sample content')}">
                <div class="form-text"><a href="#">${escapeHtml(CFG.msg?.addSample || 'Add sample text')}</a></div>
                <div class="wa-var-field__error">${escapeHtml(CFG.msg?.varError || 'Add sample text for Meta review')}</div>
            </div>`;
        });
        $('#waVarSamples').html(html);
        $('#waVarBlock').removeClass('d-none');
    }

    $(document).on('input', '.wa-var-sample', function () {
        $(this).closest('.wa-var-field').toggleClass('is-invalid', !$(this).val().trim());
        refreshLivePreview();
    });

    // ── Buttons (Meta CTA limits) ─────────────────────────────────────
    function countButtons(type) {
        let n = 0;
        $('.wa-btn-block').each(function () {
            if ($(this).find('.wa-btn-type').val() === type) n++;
        });
        return n;
    }

    function addButton(preset) {
        const opt = CTA_OPTIONS.find(o => o.type === (preset?.type || 'URL')) || CTA_OPTIONS[0];
        if (countButtons(opt.type) >= opt.max) {
            toastr.warning(opt.label + ': ' + (CFG.msg?.btnMax || 'limit reached'));
            return;
        }
        state.buttonCount++;
        const i = state.buttonCount;
        const html = `<div class="wa-btn-block" id="waBtn_${i}">
            <button type="button" class="btn btn-sm btn-link text-danger wa-btn-block__remove"><i class="bi bi-x-lg"></i></button>
            <div class="row g-2">
                <div class="col-md-4">
                    <label class="small fw-semibold">${CFG.msg?.type || 'Type'}</label>
                    <select class="form-select form-select-sm wa-btn-type">${CTA_OPTIONS.map(o =>
                        `<option value="${o.type}" ${o.type === opt.type ? 'selected' : ''}>${escapeHtml(o.label)}</option>`
                    ).join('')}</select>
                </div>
                <div class="col-md-4">
                    <label class="small fw-semibold">${CFG.msg?.btnText || 'Button text'}</label>
                    <input type="text" class="form-control form-control-sm wa-btn-text" maxlength="25" value="${escapeAttr(preset?.text || '')}">
                </div>
                <div class="col-md-4 wa-btn-extra ${opt.type === 'QUICK_REPLY' ? 'd-none' : ''}">
                    <label class="small fw-semibold wa-btn-extra-label">${escapeHtml(opt.type === 'URL' ? 'URL' : opt.type === 'PHONE_NUMBER' ? 'Phone' : 'Value')}</label>
                    <input type="text" class="form-control form-control-sm wa-btn-value" value="${escapeAttr(preset?.value || '')}">
                </div>
            </div>
        </div>`;
        $('#waButtonsList').append(html);
        const $row = $(`#waBtn_${i}`);
        $row.find('.wa-btn-type').on('change', function () {
            const t = $(this).val();
            const o = CTA_OPTIONS.find(x => x.type === t);
            $row.find('.wa-btn-extra').toggleClass('d-none', t === 'QUICK_REPLY');
            refreshLivePreview();
        });
        $row.find('input, select').on('input change', refreshLivePreview);
        $row.find('.wa-btn-block__remove').on('click', () => { $row.remove(); refreshLivePreview(); });
        refreshLivePreview();
    }

    $(document).on('click', '[data-add-btn]', function (e) {
        e.preventDefault();
        addButton({ type: $(this).data('add-btn') });
    });

    // ── Carousel ──────────────────────────────────────────────────────
    $('#waAddCard').on('click', function () {
        const n = $('#waCarouselCards .wa-carousel-item').length;
        if (n >= 10) return toastr.warning(CFG.msg?.maxCards || 'Max 10 cards');
        const c = ++state.cardCount;
        $('#waCarouselCards').append(`<div class="wa-carousel-item border rounded mb-2 p-3" data-card="${c}">
            <div class="d-flex justify-content-between mb-2"><strong>${CFG.msg?.card || 'Card'} ${n + 1}</strong>
            <button type="button" class="btn btn-sm btn-link text-danger wa-rm-card"><i class="bi bi-trash"></i></button></div>
            <div class="mb-2"><label class="small fw-semibold">${CFG.msg?.imageUrl || 'Image or video URL'} *</label>
            <input type="url" class="form-control form-control-sm wa-c-img"></div>
            <div class="mb-2"><label class="small fw-semibold">${CFG.msg?.cardBody || 'Body'} *</label>
            <textarea class="form-control form-control-sm wa-c-body" rows="2" maxlength="160"></textarea></div>
            <div class="wa-c-btns"></div>
            <button type="button" class="btn btn-sm btn-outline-secondary wa-add-c-btn"><i class="bi bi-plus"></i> ${CFG.msg?.btn || 'Button'}</button>
        </div>`);
        $('#waCardBadge').text($('#waCarouselCards .wa-carousel-item').length + '/10');
        refreshLivePreview();
    });

    $(document).on('click', '.wa-rm-card', function () {
        $(this).closest('.wa-carousel-item').remove();
        $('#waCardBadge').text($('#waCarouselCards .wa-carousel-item').length + '/10');
        refreshLivePreview();
    });

    $(document).on('click', '.wa-add-c-btn', function () {
        const $card = $(this).closest('.wa-carousel-item');
        if ($card.find('.wa-c-btn-row').length >= 2) return toastr.warning(CFG.msg?.maxCardBtns || 'Max 2 buttons per card');
        $card.find('.wa-c-btns').append(`<div class="input-group input-group-sm mb-1 wa-c-btn-row">
            <select class="form-select wa-c-type" style="max-width:7rem"><option value="QUICK_REPLY">Quick reply</option><option value="URL">URL</option><option value="PHONE_NUMBER">Phone</option></select>
            <input type="text" class="form-control wa-c-label" placeholder="Label" maxlength="25">
            <input type="text" class="form-control wa-c-val" placeholder="URL / phone">
            <button type="button" class="btn btn-outline-danger wa-rm-c-btn"><i class="bi bi-x"></i></button>
        </div>`);
        refreshLivePreview();
    });

    $(document).on('click', '.wa-rm-c-btn', function () {
        $(this).closest('.wa-c-btn-row').remove();
        refreshLivePreview();
    });

    $(document).on('input', '.wa-c-img, .wa-c-body, .wa-c-type, .wa-c-label, .wa-c-val', refreshLivePreview);

    function buildCarouselCards() {
        const cards = [];
        $('#waCarouselCards .wa-carousel-item').each(function () {
            const img = $(this).find('.wa-c-img').val().trim();
            const body = $(this).find('.wa-c-body').val().trim();
            if (!img || !body) return;
            const components = [
                { type: 'HEADER', format: 'IMAGE', example: { header_handle: [img] } },
                { type: 'BODY', text: body },
            ];
            const buttons = [];
            $(this).find('.wa-c-btn-row').each(function () {
                const type = $(this).find('.wa-c-type').val();
                const text = $(this).find('.wa-c-label').val().trim();
                const val = $(this).find('.wa-c-val').val().trim();
                if (!text) return;
                const b = { type, text };
                if (type === 'URL') b.url = val;
                if (type === 'PHONE_NUMBER') b.phone_number = val;
                buttons.push(b);
            });
            if (buttons.length) components.push({ type: 'BUTTONS', buttons });
            cards.push({ components });
        });
        return cards;
    }

    // ── Build Meta components JSON ────────────────────────────────────
    function sampleMap() {
        const m = {};
        $('.wa-var-sample').each(function () {
            m[$(this).data('var')] = $(this).val().trim() || 'sample';
        });
        return m;
    }

    function attachExamples(comp, text) {
        const vars = extractVariables(text);
        if (!vars.length) return;
        const samples = sampleMap();
        if (state.variableFormat === 'named') {
            const params = vars.map(v => ({
                param_name: v,
                example: samples[v] || 'example',
            }));
            if (comp.type === 'HEADER') {
                comp.example = { header_text_named_params: params };
            } else {
                comp.example = { body_text_named_params: params };
            }
        } else {
            const row = vars.map(v => samples[v] || 'value' + v);
            if (comp.type === 'HEADER') {
                comp.example = { header_text: row };
            } else {
                comp.example = { body_text: [row] };
            }
        }
    }

    function buildComponents() {
        if (state.templateType === 'carousel') {
            const cards = buildCarouselCards();
            const comps = [];
            const intro = $('#waBody').val().trim();
            if (intro) {
                const b = { type: 'BODY', text: intro };
                attachExamples(b, intro);
                comps.push(b);
            }
            if (cards.length) comps.push({ type: 'CAROUSEL', cards });
            return comps;
        }

        if (state.category === 'AUTHENTICATION') {
            const body = { type: 'BODY', text: $('#waBody').val().trim() };
            attachExamples(body, body.text);
            const comps = [body];
            if (state.templateType === 'otp_copy') {
                comps.push({ type: 'BUTTONS', buttons: [{ type: 'OTP', otp_type: 'COPY_CODE' }] });
            } else {
                comps.push({ type: 'BUTTONS', buttons: [{ type: 'OTP', otp_type: 'ONE_TAP' }] });
            }
            return comps;
        }

        const components = [];
        const hf = $('input[name=waHeaderFormat]:checked').val();
        if (hf && hf !== 'NONE') {
            const h = { type: 'HEADER', format: hf };
            if (hf === 'TEXT') {
                h.text = $('#waHeaderText').val();
                attachExamples(h, h.text);
            } else {
                h.example = { header_handle: [$('#waHeaderMediaUrl').val() || $('#waMediaSample').val()] };
            }
            components.push(h);
        }

        const bodyTxt = $('#waBody').val().trim();
        if (bodyTxt) {
            const b = { type: 'BODY', text: bodyTxt };
            attachExamples(b, bodyTxt);
            components.push(b);
        }

        const foot = $('#waFooter').val().trim();
        if (foot) components.push({ type: 'FOOTER', text: foot });

        const buttons = [];
        $('.wa-btn-block').each(function () {
            const type = $(this).find('.wa-btn-type').val();
            const text = $(this).find('.wa-btn-text').val().trim();
            const val = $(this).find('.wa-btn-value').val().trim();
            if (!text && type !== 'COPY_CODE') return;
            const btn = { type, text };
            if (type === 'URL') btn.url = val;
            if (type === 'PHONE_NUMBER') btn.phone_number = val;
            if (type === 'VOICE_CALL') { /* text only per Meta */ }
            if (type === 'COPY_CODE') btn.example = val || 'CODE';
            if (type === 'FLOW') btn.flow_id = val;
            buttons.push(btn);
        });
        if (buttons.length) components.push({ type: 'BUTTONS', buttons });

        return components;
    }

    // ── Previews ──────────────────────────────────────────────────────
    function formatText(text) {
        if (!text) return '';
        const sm = sampleMap();
        let out = escapeHtml(text);
        if (state.variableFormat === 'named') {
            out = out.replace(/\{\{([a-z_][a-z0-9_]*)\}\}/gi, (_, n) =>
                `<strong>${escapeHtml(sm[n] || '{{' + n + '}}')}</strong>`);
        } else {
            out = out.replace(/\{\{(\d+)\}\}/g, (_, n) =>
                `<strong>${escapeHtml(sm[n] || '{{' + n + '}}')}</strong>`);
        }
        out = out.replace(/\*([^*]+)\*/g, '<strong>$1</strong>')
            .replace(/_([^_]+)_/g, '<em>$1</em>')
            .replace(/~([^~]+)~/g, '<s>$1</s>')
            .replace(/\n/g, '<br>');
        return out;
    }

    function iconFor(type) {
        return { URL: 'bi-box-arrow-up-right', PHONE_NUMBER: 'bi-telephone', VOICE_CALL: 'bi-telephone-fill',
            COPY_CODE: 'bi-clipboard', FLOW: 'bi-list-ul', QUICK_REPLY: 'bi-reply', OTP: 'bi-clipboard' }[type] || 'bi-chevron-right';
    }

    function bubbleHtml() {
        const comps = buildComponents();
        if (!comps.length) return `<div class="text-muted small text-center py-4">${escapeHtml(CFG.msg?.previewEmpty || '')}</div>`;

        let h = '<div class="wa-preview-bubble">';
        comps.forEach(c => {
            if (c.type === 'HEADER' && c.format === 'TEXT') h += `<div class="p-2 pb-0 fw-semibold small">${formatText(c.text)}</div>`;
            if (c.type === 'HEADER' && c.format && c.format !== 'TEXT') {
                const url = $('#waHeaderMediaUrl').val();
                h += url && c.format === 'IMAGE'
                    ? `<div class="wa-preview-bubble__media"><img src="${escapeAttr(url)}" alt=""></div>`
                    : `<div class="wa-preview-bubble__media"><i class="bi bi-image fs-2 opacity-50"></i></div>`;
            }
            if (c.type === 'BODY') h += `<div class="wa-preview-bubble__body">${formatText(c.text)}</div>`;
            if (c.type === 'FOOTER') h += `<div class="px-2 pb-1 small opacity-75">${escapeHtml(c.text)}</div>`;
            if (c.type === 'CAROUSEL') {
                h += '<div class="d-flex gap-1 p-2 overflow-auto">';
                (c.cards || []).forEach(card => {
                    h += '<div class="border rounded flex-shrink-0" style="width:130px">';
                    h += '<div class="wa-preview-bubble__media" style="min-height:72px"></div>';
                    const b = card.components?.find(x => x.type === 'BODY');
                    if (b) h += `<div class="p-1 small">${formatText(b.text)}</div>`;
                    h += '</div>';
                });
                h += '</div>';
            }
        });
        const btns = comps.find(c => c.type === 'BUTTONS');
        if (btns) {
            btns.buttons.forEach(b => {
                const label = b.text || (b.type === 'OTP' ? 'Copy code' : b.type);
                h += `<div class="wa-preview-bubble__cta"><i class="bi ${iconFor(b.type)}"></i> ${escapeHtml(label)}</div>`;
            });
        }
        h += `<div class="text-end pe-2 pb-1" style="font-size:10px;opacity:.5">${new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div></div>`;
        return h;
    }

    function refreshLivePreview() {
        const html = `<div class="wa-preview-device">${bubbleHtml()}</div>`;
        $('#waLivePreview').html(html);
    }

    function updateSummaryChip() {
        const name = $('#waName').val() || 'your_template_name';
        const lang = $('#waLanguage option:selected').text().split('(')[0].trim();
        const cat = { MARKETING: 'Marketing', UTILITY: 'Utility', AUTHENTICATION: 'Authentication' }[state.category];
        const typeObj = (TEMPLATE_TYPES[state.category] || []).find(t => t.id === state.templateType);
        $('#waSummaryChip').html(
            `<i class="bi bi-megaphone-fill text-primary"></i> <strong>${escapeHtml(name)}</strong> · ${escapeHtml(lang)} · ${escapeHtml(cat)} · ${escapeHtml(typeObj?.title || '')}`
        );
    }

    $('#waName, #waLanguage').on('input change', updateSummaryChip);

    function renderReview() {
        const comps = buildComponents();
        $('#waReviewJson').text(JSON.stringify(comps, null, 2));
        $('#waReviewPreview').html(`<div class="wa-preview-device">${bubbleHtml()}</div>`);
        $('#waReviewName').text($('#waName').val());
        $('#waReviewDevice').text($('#waDevice option:selected').text());
        $('#waReviewCategory').text(state.category);
        $('#waReviewLang').text($('#waLanguage').val());
    }

    // ── Submit ────────────────────────────────────────────────────────
    function submitTemplate() {
        if (!validateEditor()) {
            goToStep(1);
            return;
        }
        const $btn = $('#waBtnSubmit').prop('disabled', true);
        const orig = $btn.html();
        $btn.html('<span class="spinner-border spinner-border-sm"></span>');

        $.ajax({
            method: 'POST',
            url: CFG.routes?.store,
            headers: { 'X-CSRF-TOKEN': CFG.csrf },
            data: {
                device_id: $('#waDevice').val(),
                name: $('#waName').val().trim(),
                category: state.category,
                language: $('#waLanguage').val(),
                components: JSON.stringify(buildComponents()),
            },
            success(res) {
                toastr.success(res.message);
                setTimeout(() => { window.location = CFG.routes?.index; }, 1500);
            },
            error(err) {
                toastr.error(err.responseJSON?.message || CFG.msg?.submitFailed || 'Submit failed');
                $btn.prop('disabled', false).html(orig);
            },
        });
    }

    function escapeHtml(s) { return $('<div>').text(s || '').html(); }
    function escapeAttr(s) { return String(s || '').replace(/"/g, '&quot;'); }

    $('#waBtnPrev').on('click', () => goToStep(state.step - 1));
    $('#waBtnNext').on('click', () => goToStep(state.step + 1));
    $('#waBtnSubmit').on('click', submitTemplate);
    $('#waDiscard').on('click', () => {
        if (confirm(CFG.msg?.discard || 'Discard?')) window.location = CFG.routes?.index;
    });

    // Init
    const hash = window.location.hash.match(/step-(\d)/);
    renderTypeList();
    renderSetupMeta();
    $('input[name=waHeaderFormat]:checked').each(function () {
        $(`.wa-hdr-pill[data-format="${$(this).val()}"]`).addClass('active');
    });
    goToStep(hash ? Math.min(2, parseInt(hash[1], 10) - 1) : 0);

})(jQuery, window);
