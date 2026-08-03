<x-layout-dashboard title="{{ __('Create Template') }}">

    <link href="{{ asset('css/template-builder.css') }}?v={{ @filemtime(public_path('themes/' . env('THEME_NAME', 'mpwa') . '/css/template-builder.css')) ?: time() }}" rel="stylesheet" />

    <x-page-header title="{{ __('Create template') }}"
        subtitle="{{ __('Aligned with Meta template components: header, body, footer, and buttons') }}"
        :breadcrumb="[__('Templates'), __('Create')]">
        <a href="{{ route('templates.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> {{ __('Back to library') }}
        </a>
    </x-page-header>

    <div id="templateBuilder" class="wa-tpl">
        <div class="wa-tpl__frame">
            {{-- Meta stepper: Set up template → Edit template → Submit for Review --}}
            <nav class="wa-tpl__stepper" aria-label="{{ __('Progress') }}">
                <div class="wa-tpl__step is-active" data-step="0">
                    <span class="wa-tpl__step-icon">1</span>
                    <span>{{ __('Set up template') }}</span>
                </div>
                <div class="wa-tpl__step" data-step="1">
                    <span class="wa-tpl__step-icon">2</span>
                    <span>{{ __('Edit template') }}</span>
                </div>
                <div class="wa-tpl__step" data-step="2">
                    <span class="wa-tpl__step-icon">3</span>
                    <span>{{ __('Submit for Review') }}</span>
                </div>
            </nav>

            <div class="wa-tpl__split">
                {{-- ═══ STEP 1: Set up (Meta layout) ═══ --}}
                <div class="wa-tpl-pane is-active" data-step="0">
                    <div class="wa-tpl__main">
                        <h2 class="wa-tpl__h2">{{ __('Set up your template') }}</h2>
                        <p class="small text-muted mb-3">
                            {{ __('Choose a category and template type. Meta validates category against content.') }}
                            <a href="https://developers.facebook.com/documentation/business-messaging/whatsapp/templates/template-categorization" target="_blank" rel="noopener">{{ __('Category guidelines') }}</a>
                        </p>

                        <div class="wa-cat-tabs" role="tablist">
                            <button type="button" class="wa-cat-tab is-active" data-category="MARKETING">
                                <i class="bi bi-megaphone"></i>
                                {{ __('Marketing') }}
                            </button>
                            <button type="button" class="wa-cat-tab" data-category="UTILITY">
                                <i class="bi bi-bell"></i>
                                {{ __('Utility') }}
                            </button>
                            <button type="button" class="wa-cat-tab" data-category="AUTHENTICATION">
                                <i class="bi bi-key"></i>
                                {{ __('Authentication') }}
                            </button>
                            <input type="radio" name="waCategory" value="MARKETING" class="d-none" checked>
                            <input type="radio" name="waCategory" value="UTILITY" class="d-none">
                            <input type="radio" name="waCategory" value="AUTHENTICATION" class="d-none">
                        </div>

                        <div id="waTypeList" class="wa-type-list"></div>
                    </div>

                    <aside class="wa-tpl__aside">
                        <p class="wa-preview-panel__title">{{ __('Template preview') }}</p>
                        <div id="waSetupPreview" class="wa-preview-device"></div>
                        <div class="wa-preview-meta">
                            <h4>{{ __('This template is good for') }}</h4>
                            <ul id="waGoodFor"></ul>
                            <h4>{{ __('Template areas that you can customise') }}</h4>
                            <ul id="waCustomize"></ul>
                            <p class="small text-muted mb-0">
                                <span id="waSetupCatLabel">Marketing</span> · <span id="waSetupTypeLabel">Default</span>
                            </p>
                        </div>
                    </aside>
                </div>

                {{-- ═══ STEP 2: Edit (Meta layout) ═══ --}}
                <div class="wa-tpl-pane" data-step="1">
                    <div class="wa-tpl__main">
                        <div class="wa-summary-chip" id="waSummaryChip"></div>

                        <div class="wa-form-card mb-3">
                            <p class="wa-form-card__title">{{ __('WhatsApp account') }}</p>
                            <select id="waDevice" class="form-select" required>
                                <option value="">{{ __('Select connected device') }}</option>
                                @foreach ($devices as $device)
                                    <option value="{{ $device->id }}">{{ $device->meta_profile['verified_name'] ?? $device->body }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="wa-form-card">
                            <p class="wa-form-card__title">{{ __('Template name and language') }}</p>
                            <div class="wa-field-row">
                                <div class="wa-field">
                                    <label for="waName">{{ __('Name your template') }} <span class="text-danger">*</span>
                                        <span class="wa-char-inline" id="waNameCount">0/512</span>
                                    </label>
                                    <input type="text" id="waName" class="form-control font-monospace" maxlength="512" placeholder="your_template_name" pattern="[a-z0-9_]+" required>
                                </div>
                                <div class="wa-field">
                                    <label for="waLanguage">{{ __('Select language') }} <span class="text-danger">*</span></label>
                                    <select id="waLanguage" class="form-select">
                                        @foreach ([
                                            'en' => 'English', 'en_US' => 'English (US)', 'en_GB' => 'English (UK)',
                                            'ar' => 'Arabic', 'es' => 'Spanish', 'fr' => 'French', 'de' => 'German',
                                            'pt_BR' => 'Portuguese (BR)', 'hi' => 'Hindi', 'id' => 'Indonesian',
                                        ] as $code => $label)
                                            <option value="{{ $code }}">{{ $label }} ({{ $code }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="wa-form-card">
                            <p class="wa-form-card__title">{{ __('Content') }}</p>
                            <p class="wa-form-card__hint">
                                {{ __('Add a header, body and footer. Cloud API reviews templates and variable samples.') }}
                                <a href="https://developers.facebook.com/documentation/business-messaging/whatsapp/templates/components" target="_blank" rel="noopener">{{ __('Template components') }}</a>
                            </p>

                            <div class="wa-content-grid wa-var-format-row" id="waVarFormatRow">
                                <div class="wa-field">
                                    <label for="waVarFormat">{{ __('Type of variable') }}</label>
                                    <select id="waVarFormat" class="form-select">
                                        <option value="positional">{{ __('Number') }} (&#123;&#123;1&#125;&#125;, &#123;&#123;2&#125;&#125;…)</option>
                                        <option value="named">{{ __('Name') }} (&#123;&#123;first_name&#125;&#125;…)</option>
                                    </select>
                                </div>
                                <div class="wa-field" id="waMediaSampleRow">
                                    <label for="waMediaSample">{{ __('Media sample') }} <span class="text-muted fw-normal">({{ __('Optional') }})</span></label>
                                    <select id="waMediaSample" class="form-select">
                                        <option value="">{{ __('None') }}</option>
                                        <option value="image">{{ __('Image') }}</option>
                                        <option value="video">{{ __('Video') }}</option>
                                        <option value="document">{{ __('Document') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div id="waHeaderBlock" class="mb-3">
                                <label class="fw-semibold small">{{ __('Header') }} <span class="text-muted fw-normal">({{ __('Optional') }})</span></label>
                                <div class="d-flex flex-wrap gap-1 mb-2 mt-1">
                                    @foreach (['NONE' => __('None'), 'TEXT' => __('Text'), 'IMAGE' => __('Image'), 'VIDEO' => __('Video'), 'DOCUMENT' => __('Document')] as $fmt => $lbl)
                                        <button type="button" class="btn btn-sm btn-outline-secondary wa-hdr-pill {{ $fmt === 'NONE' ? 'active' : '' }}" data-format="{{ $fmt }}">{{ $lbl }}</button>
                                        <input type="radio" name="waHeaderFormat" value="{{ $fmt }}" class="d-none" id="waHF{{ $fmt }}" {{ $fmt === 'NONE' ? 'checked' : '' }}>
                                    @endforeach
                                </div>
                                <div id="waHeaderTextRow" class="d-none">
                                    <input type="text" id="waHeaderText" class="form-control" maxlength="60" placeholder="{{ __('Add a short line of text') }}">
                                    <button type="button" class="btn btn-link btn-sm p-0" id="waHdrAddVar">+ {{ __('Add variable') }}</button>
                                </div>
                                <div id="waHeaderMediaRow" class="d-none">
                                    <input type="url" id="waHeaderMediaUrl" class="form-control" placeholder="https://…">
                                    <div class="form-text">{{ __('Use Resumable Upload API for production; public URL for testing.') }}</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="fw-semibold small">{{ __('Body') }} <span class="text-danger">*</span>
                                    <span class="wa-char-inline" id="waBodyCount">0/1024</span>
                                </label>
                                <div class="wa-body-wrap mt-1">
                                    <div class="wa-body-toolbar">
                                        <button type="button" id="waFmtBold" title="{{ __('Bold') }}"><i class="bi bi-type-bold"></i></button>
                                        <button type="button" id="waFmtItalic" title="{{ __('Italic') }}"><i class="bi bi-type-italic"></i></button>
                                        <button type="button" id="waFmtStrike" title="{{ __('Strikethrough') }}"><i class="bi bi-type-strikethrough"></i></button>
                                        <button type="button" id="waFmtCode" title="{{ __('Monospace') }}"><i class="bi bi-code"></i></button>
                                        <button type="button" class="wa-add-var" id="waFmtVar">+ {{ __('Add variable') }}</button>
                                    </div>
                                    <textarea id="waBody" class="form-control" rows="5" maxlength="1024" placeholder="{{ __('Enter text in') }} {{ app()->getLocale() === 'en' ? 'English' : 'selected language' }}"></textarea>
                                </div>
                            </div>

                            <div id="waVarBlock" class="wa-var-block d-none">
                                <p class="wa-var-block__title">{{ __('Variable samples') }}</p>
                                <p class="wa-var-block__desc">{{ __('Include samples of all variables to help Meta review. Samples are required.') }}</p>
                                <div id="waVarSamples"></div>
                            </div>

                            <div id="waFooterBlock" class="mb-3">
                                <label class="fw-semibold small">{{ __('Footer') }} <span class="text-muted fw-normal">({{ __('Optional') }})</span>
                                    <span class="wa-char-inline" id="waFooterCount">0/60</span>
                                </label>
                                <input type="text" id="waFooter" class="form-control mt-1" maxlength="60">
                            </div>

                            <div id="waCtaBlock">
                                <label class="fw-semibold small">{{ __('Buttons') }} <span class="text-muted fw-normal">({{ __('Optional') }})</span></label>
                                <p class="small text-muted">{{ __('Up to 10 buttons total; limits apply per type (see Meta docs).') }}</p>
                                <div id="waButtonsList" class="mt-2"></div>
                                <div class="dropdown wa-add-btn-dropdown d-inline-block mt-2">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        + {{ __('Add button') }}
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="#" data-add-btn="URL">{{ __('Visit website') }}<small>{{ __('Max 2') }}</small></a></li>
                                        <li><a class="dropdown-item" href="#" data-add-btn="PHONE_NUMBER">{{ __('Call phone number') }}<small>{{ __('1 maximum') }}</small></a></li>
                                        <li><a class="dropdown-item" href="#" data-add-btn="VOICE_CALL">{{ __('Call on WhatsApp') }}</a></li>
                                        <li><a class="dropdown-item" href="#" data-add-btn="COPY_CODE">{{ __('Copy offer code') }}<small>{{ __('1 maximum') }}</small></a></li>
                                        <li><a class="dropdown-item" href="#" data-add-btn="FLOW">{{ __('Complete flow') }}<small>{{ __('1 maximum') }}</small></a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="#" data-add-btn="QUICK_REPLY">{{ __('Quick reply') }}</a></li>
                                    </ul>
                                </div>
                            </div>

                            <div id="waCarouselBlock" class="d-none mt-3 pt-3 border-top">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-semibold">{{ __('Carousel cards') }} <span class="badge bg-secondary" id="waCardBadge">0/10</span></span>
                                    <button type="button" id="waAddCard" class="btn btn-sm btn-outline-primary">+ {{ __('Add card') }}</button>
                                </div>
                                <p class="small text-muted">{{ __('Marketing media card carousel: 2–10 cards, each with media header and optional buttons.') }}</p>
                                <div id="waCarouselCards"></div>
                            </div>

                            <div id="waAuthNotice" class="alert alert-info small d-none mt-3 mb-0">
                                <i class="bi bi-shield-lock"></i>
                                {{ __('Authentication templates require') }} <code>@{{1}}</code> {{ __('in the body and an OTP button. See') }}
                                <a href="https://developers.facebook.com/documentation/business-messaging/whatsapp/templates/authentication-templates/authentication-templates" target="_blank" rel="noopener">{{ __('Authentication templates') }}</a>.
                            </div>
                        </div>
                    </div>

                    <aside class="wa-tpl__aside">
                        <div class="wa-live-preview__head">
                            <span>{{ __('Template preview') }}</span>
                        </div>
                        <div id="waLivePreview"></div>
                    </aside>
                </div>

                {{-- ═══ STEP 3: Submit ═══ --}}
                <div class="wa-tpl-pane" data-step="2">
                    <div class="wa-tpl__main">
                        <h2 class="wa-tpl__h2">{{ __('Submit for Review') }}</h2>
                        <p class="text-muted small">{{ __('Meta typically reviews templates within 24–48 hours.') }}</p>
                        <dl class="row small mb-4">
                            <dt class="col-sm-3">{{ __('Device') }}</dt><dd class="col-sm-9" id="waReviewDevice">—</dd>
                            <dt class="col-sm-3">{{ __('Name') }}</dt><dd class="col-sm-9 font-monospace" id="waReviewName">—</dd>
                            <dt class="col-sm-3">{{ __('Category') }}</dt><dd class="col-sm-9" id="waReviewCategory">—</dd>
                            <dt class="col-sm-3">{{ __('Language') }}</dt><dd class="col-sm-9" id="waReviewLang">—</dd>
                        </dl>
                        <p class="fw-semibold small">{{ __('Components JSON') }}</p>
                        <pre class="wa-review-json" id="waReviewJson">{}</pre>
                    </div>
                    <aside class="wa-tpl__aside">
                        <p class="wa-preview-panel__title">{{ __('Final preview') }}</p>
                        <div id="waReviewPreview"></div>
                    </aside>
                </div>
            </div>

            <div class="wa-tpl__footer">
                <button type="button" class="btn btn-link text-muted" id="waDiscard">{{ __('Discard') }}</button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" id="waBtnPrev" style="display:none">{{ __('Previous') }}</button>
                    <button type="button" class="btn btn-primary" id="waBtnNext">{{ __('Next') }}</button>
                    <button type="button" class="btn btn-primary" id="waBtnSubmit" style="display:none">{{ __('Submit for Review') }}</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.TEMPLATE_BUILDER = {
            csrf: @json(csrf_token()),
            routes: {
                store: @json(route('templates.store')),
                index: @json(route('templates.index')),
            },
            catalogPreviewImg: 'https://scontent.whatsapp.net/v/t61.29466-34/placeholder.jpg',
            msg: {!! json_encode([
                'selectDevice' => __('Please select a WhatsApp account'),
                'invalidName' => __('Use lowercase letters, numbers, and underscores only.'),
                'bodyRequired' => __('Body text is required'),
                'authBody' => 'Authentication body must include @{{1}} for the OTP',
                'varRequired' => __('Add sample text for all variables'),
                'varPlaceholder' => __('Enter content for this variable'),
                'varError' => __('Add sample text'),
                'addSample' => __('Add sample text'),
                'varNamePrompt' => __('Variable name (e.g. first_name)'),
                'carouselRequired' => __('Add at least 2 complete carousel cards'),
                'previewEmpty' => __('Start editing to see preview'),
                'discard' => __('Discard changes?'),
                'submitFailed' => __('Failed to submit'),
                'btnMax' => __('Maximum reached for this button type'),
                'maxCards' => __('Maximum 10 cards'),
                'maxCardBtns' => __('Max 2 buttons per card'),
            ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!},
        };
        $('#waName').on('input', function () {
            $('#waNameCount').text(this.value.length + '/512');
        });
    </script>
    <script src="{{ asset('js/template-builder.js') }}?v={{ @filemtime(public_path('themes/' . env('THEME_NAME', 'mpwa') . '/js/template-builder.js')) ?: time() }}"></script>

</x-layout-dashboard>
