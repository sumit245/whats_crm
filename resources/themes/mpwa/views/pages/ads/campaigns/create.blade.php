<x-layout-dashboard title="{{ __('Create Ad Campaign') }}">

<x-page-header title="{{ __('Create Ad Campaign') }}"
    subtitle="{{ __('Launch a multi-channel campaign across all connected platforms') }}"
    :breadcrumb="[__('Ads Manager'), __('Campaigns'), __('Create')]" />

{{-- ── Multi-step wizard ─────────────────────────────────────────────────────── --}}
<div class="row justify-content-center">
<div class="col-xl-10">

{{-- Step progress --}}
<div class="d-flex align-items-center mb-4 gap-0" id="stepNav">
    @php
        $steps = [__('Basics'), __('Channels'), __('Creative'), __('Audience'), __('Budget'), __('Review')];
    @endphp
    @foreach($steps as $i => $s)
    <div class="step-item d-flex align-items-center {{ $loop->last ? '' : 'flex-fill' }}">
        <div class="step-circle d-flex align-items-center justify-content-center rounded-circle fw-bold
            {{ $i === 0 ? 'bg-primary text-white' : 'bg-light text-muted border' }}"
            style="width:34px;height:34px;font-size:13px;flex-shrink:0" id="stepCircle{{ $i }}">
            {{ $i + 1 }}
        </div>
        <div class="ms-2 small fw-semibold {{ $i === 0 ? 'text-primary' : 'text-muted' }}" id="stepLabel{{ $i }}">{{ $s }}</div>
        @if(!$loop->last)
        <div class="flex-fill border-top mx-2" style="min-width:20px"></div>
        @endif
    </div>
    @endforeach
</div>

<form method="POST" action="{{ route('ads.campaigns.store') }}" enctype="multipart/form-data" id="campaignForm">
@csrf

{{-- ── Step 1: Basics ───────────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-3 step-panel" id="step0">
    <div class="card-body">
        <h6 class="fw-semibold mb-3">{{ __('Campaign Basics') }}</h6>
        <div class="mb-3">
            <label class="form-label fw-semibold">{{ __('Campaign Name') }} <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="{{ __('e.g. Summer Sale 2026') }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">{{ __('Objective') }} <span class="text-danger">*</span></label>
            <div class="row g-2">
                @php
                    $objectives = [
                        ['value' => 'ctwa',       'label' => 'Click-to-WhatsApp',  'icon' => 'whatsapp',         'desc' => 'Drive conversations on WhatsApp'],
                        ['value' => 'awareness',  'label' => 'Brand Awareness',    'icon' => 'eye-fill',          'desc' => 'Maximize reach and visibility'],
                        ['value' => 'traffic',    'label' => 'Traffic',            'icon' => 'cursor-fill',       'desc' => 'Drive clicks to your website'],
                        ['value' => 'engagement', 'label' => 'Engagement',         'icon' => 'hand-thumbs-up',    'desc' => 'Likes, comments, shares'],
                        ['value' => 'leads',      'label' => 'Lead Generation',    'icon' => 'person-plus-fill',  'desc' => 'Collect contact information'],
                        ['value' => 'sales',      'label' => 'Sales',              'icon' => 'cart-fill',         'desc' => 'Drive purchases and conversions'],
                    ];
                @endphp
                @foreach($objectives as $obj)
                <div class="col-6 col-md-4">
                    <label class="card border p-3 d-flex flex-row align-items-center gap-2 cursor-pointer objective-card h-100"
                           style="cursor:pointer" for="obj_{{ $obj['value'] }}">
                        <input type="radio" name="objective" value="{{ $obj['value'] }}" id="obj_{{ $obj['value'] }}"
                               class="d-none objective-radio" {{ $obj['value'] === 'ctwa' ? 'checked' : '' }}>
                        <i class="bi bi-{{ $obj['icon'] }} fs-4 text-primary"></i>
                        <div>
                            <div class="fw-semibold small">{{ $obj['label'] }}</div>
                            <div class="text-muted" style="font-size:11px">{{ $obj['desc'] }}</div>
                        </div>
                    </label>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- ── Step 2: Channels ──────────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-3 step-panel d-none" id="step1">
    <div class="card-body">
        <h6 class="fw-semibold mb-3">{{ __('Select Channels') }}</h6>
        @if($channels->isEmpty())
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            {{ __('No active channels connected.') }}
            <a href="{{ route('ads.channels.index') }}">{{ __('Connect a channel first') }}</a>
        </div>
        @else
        <div class="row g-3">
            @foreach($channels as $ch)
            <div class="col-md-6 col-lg-4">
                <label class="card border p-3 channel-card h-100" style="cursor:pointer" for="ch_{{ $ch->id }}">
                    <input type="checkbox" name="channel_ids[]" value="{{ $ch->id }}" id="ch_{{ $ch->id }}" class="d-none channel-checkbox">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi {{ $ch->typeIcon() }} text-{{ $ch->typeColor() }} fs-4"></i>
                        <div>
                            <div class="fw-semibold small">{{ $ch->name }}</div>
                            <div class="text-muted" style="font-size:11px">{{ $ch->typeLabel() }}</div>
                        </div>
                    </div>
                    <div class="mt-2">
                        <label class="form-label form-label-sm">{{ __('Placement') }}</label>
                        <select name="placement_type_{{ $ch->id }}" class="form-select form-select-sm">
                            @php
                                $placements = match($ch->type) {
                                    'meta', 'facebook' => ['feed', 'stories', 'reels', 'ctwa'],
                                    'instagram'        => ['feed', 'stories', 'reels'],
                                    'linkedin'         => ['feed', 'sponsored'],
                                    'telegram'         => ['direct_message'],
                                    'email'            => ['direct_message'],
                                    default            => ['feed'],
                                };
                            @endphp
                            @foreach($placements as $pl)
                            <option value="{{ $pl }}">{{ ucfirst(str_replace('_', ' ', $pl)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mt-2">
                        <label class="form-label form-label-sm">{{ __('Budget override ($/day)') }}</label>
                        <input type="number" name="budget_{{ $ch->id }}" class="form-control form-control-sm" placeholder="{{ __('Leave blank to use campaign budget') }}" step="0.01" min="0">
                    </div>
                </label>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

{{-- ── Step 3: Creative Studio ───────────────────────────────────────────────── --}}
<div class="step-panel d-none" id="step2">
<div class="row g-3 align-items-start">

{{-- Left: form --}}
<div class="col-lg-7">
<div class="card border-0 shadow-sm">
<div class="card-body">
    <h6 class="fw-semibold mb-3">{{ __('Creative Studio') }}</h6>

    {{-- Existing creative picker --}}
    <div class="mb-3">
        <label class="form-label fw-semibold">{{ __('Use Existing Creative') }}</label>
        <select name="creative_id" class="form-select" id="existingCreative">
            <option value="">{{ __('— Compose new below —') }}</option>
            @foreach($creatives as $cr)
            <option value="{{ $cr->id }}"
                {{ request('creative_id') == $cr->id ? 'selected' : '' }}
                data-headline="{{ $cr->headline }}"
                data-body="{{ $cr->body }}"
                data-cta="{{ $cr->cta_text }}"
                data-format="{{ $cr->format }}"
                data-media="{{ $cr->media_paths ? asset('storage/'.$cr->media_paths[0]) : '' }}">
                {{ $cr->name }} ({{ ucfirst($cr->format) }})
            </option>
            @endforeach
        </select>
    </div>

    <div id="newCreativeForm">
    <hr class="my-3">
    <p class="text-muted small mb-3">{{ __('Or compose a new creative:') }}</p>

    {{-- Format selector as icon tabs --}}
    <div class="mb-3">
        <label class="form-label fw-semibold">{{ __('Format') }}</label>
        <div class="d-flex flex-wrap gap-2" id="formatTabs">
            @php
            $formats = [
                ['value'=>'text',     'icon'=>'bi-fonts',          'label'=>'Text'],
                ['value'=>'image',    'icon'=>'bi-image',          'label'=>'Image'],
                ['value'=>'video',    'icon'=>'bi-play-circle',    'label'=>'Video'],
                ['value'=>'carousel', 'icon'=>'bi-images',         'label'=>'Carousel'],
                ['value'=>'story',    'icon'=>'bi-phone',          'label'=>'Story'],
                ['value'=>'reel',     'icon'=>'bi-film',           'label'=>'Reel'],
            ];
            @endphp
            @foreach($formats as $fmt)
            <button type="button" class="btn btn-sm format-tab {{ $fmt['value'] === 'image' ? 'btn-primary' : 'btn-outline-secondary' }}"
                data-format="{{ $fmt['value'] }}">
                <i class="bi {{ $fmt['icon'] }} me-1"></i>{{ __($fmt['label']) }}
            </button>
            @endforeach
        </div>
        <input type="hidden" name="format" id="formatHidden" value="image">
    </div>

    <div class="mb-3">
        <label class="form-label fw-semibold">{{ __('Creative Name') }}</label>
        <input type="text" name="creative_name" id="cName" class="form-control"
               placeholder="{{ __('Internal label e.g. Summer Sale Hero') }}">
    </div>

    <div class="mb-3">
        <label class="form-label fw-semibold">{{ __('Headline') }}</label>
        <input type="text" name="creative_headline" id="cHeadline" class="form-control"
               placeholder="{{ __('Attention-grabbing headline') }}" maxlength="255">
        <div class="form-text text-end"><span id="headlineCount">0</span>/255</div>
    </div>

    <div class="mb-3">
        <label class="form-label fw-semibold">{{ __('Body / Caption') }}</label>
        <textarea name="creative_body" id="cBody" class="form-control" rows="4"
                  placeholder="{{ __('Your ad copy...') }}" maxlength="2000"></textarea>
        <div class="form-text text-end"><span id="bodyCount">0</span>/2000</div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold">{{ __('CTA Text') }}</label>
            <input type="text" name="creative_cta_text" id="cCta" class="form-control"
                   placeholder="{{ __('Shop Now, Learn More…') }}" maxlength="50">
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">{{ __('CTA URL') }}</label>
            <input type="url" name="creative_cta_url" class="form-control" placeholder="https://">
        </div>
    </div>

    {{-- Media upload (non-carousel) --}}
    <div id="mediaUploadSection" class="mb-3">
        <label class="form-label fw-semibold">{{ __('Media') }}</label>
        <div class="media-drop-zone border rounded-3 p-4 text-center position-relative" id="mediaDropZone"
             style="border-style:dashed!important;cursor:pointer;background:#fafafa">
            <i class="bi bi-cloud-upload fs-2 text-muted"></i>
            <div class="text-muted small mt-1">{{ __('Drag & drop or click to upload') }}</div>
            <div class="text-muted" style="font-size:11px">{{ __('Images & videos supported') }}</div>
            <input type="file" name="creative_media[]" id="mediaInput" class="position-absolute top-0 start-0 w-100 h-100 opacity-0" multiple accept="image/*,video/*" style="cursor:pointer">
        </div>
        <div id="mediaThumbs" class="d-flex flex-wrap gap-2 mt-2"></div>
    </div>

    {{-- Carousel builder --}}
    <div id="carouselSection" class="d-none mb-3">
        <label class="form-label fw-semibold">{{ __('Carousel Cards') }}</label>
        <div id="carouselCards">
            <div class="card border mb-2 carousel-card">
                <div class="card-body p-2">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-3">
                            <input type="url" class="form-control form-control-sm" placeholder="{{ __('Image URL') }}" data-field="image">
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control form-control-sm" placeholder="{{ __('Headline') }}" data-field="headline">
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control form-control-sm" placeholder="{{ __('Description') }}" data-field="description">
                        </div>
                        <div class="col-md-2">
                            <input type="url" class="form-control form-control-sm" placeholder="{{ __('Link') }}" data-field="cta_url">
                        </div>
                        <div class="col-md-1">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-card"><i class="bi bi-trash3"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary mt-1" id="addCard">
            <i class="bi bi-plus-lg me-1"></i>{{ __('Add Card') }}
        </button>
        <input type="hidden" name="carousel_cards" id="carouselCardsJson">
    </div>

    </div>{{-- /newCreativeForm --}}
</div>
</div>
</div>

{{-- Right: live preview pane --}}
<div class="col-lg-5">
<div class="card border-0 shadow-sm" style="position:sticky;top:70px">
    <div class="card-header border-0 bg-light d-flex align-items-center gap-2">
        <i class="bi bi-eye text-muted"></i>
        <span class="fw-semibold small">{{ __('Live Preview') }}</span>
        <div class="ms-auto d-flex gap-1 flex-wrap" id="previewTabs">
            <button type="button" class="btn btn-xs btn-primary preview-tab active" data-channel="whatsapp" title="WhatsApp CTWA">
                <i class="bi bi-whatsapp"></i>
            </button>
            <button type="button" class="btn btn-xs btn-outline-secondary preview-tab" data-channel="facebook" title="Facebook">
                <i class="bi bi-facebook"></i>
            </button>
            <button type="button" class="btn btn-xs btn-outline-secondary preview-tab" data-channel="instagram" title="Instagram">
                <i class="bi bi-instagram"></i>
            </button>
            <button type="button" class="btn btn-xs btn-outline-secondary preview-tab" data-channel="linkedin" title="LinkedIn">
                <i class="bi bi-linkedin"></i>
            </button>
            <button type="button" class="btn btn-xs btn-outline-secondary preview-tab" data-channel="telegram" title="Telegram">
                <i class="bi bi-telegram"></i>
            </button>
            <button type="button" class="btn btn-xs btn-outline-secondary preview-tab" data-channel="email" title="Email">
                <i class="bi bi-envelope-fill"></i>
            </button>
        </div>
    </div>
    <div class="card-body p-3" id="previewArea" style="min-height:400px">
        {{-- Rendered by JS --}}
    </div>
</div>
</div>

</div>
</div>{{-- /step2 --}}

{{-- ── Step 4: Audience ─────────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-3 step-panel d-none" id="step3">
    <div class="card-body">
        <h6 class="fw-semibold mb-3">{{ __('Audience Targeting') }}</h6>
        <div class="mb-3">
            <label class="form-label fw-semibold">{{ __('Use Existing Segment') }}</label>
            <select name="target_segment_id" class="form-select">
                <option value="">{{ __('No segment — use custom targeting below') }}</option>
                @foreach($segments as $seg)
                <option value="{{ $seg->id }}">{{ $seg->name }} ({{ $seg->contacts_count ?? '?' }} {{ __('contacts') }})</option>
                @endforeach
            </select>
        </div>
        <hr class="my-3">
        <p class="text-muted small mb-3">{{ __('Custom targeting (applied to paid ads):') }}</p>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label fw-semibold">{{ __('Min Age') }}</label>
                <input type="number" name="age_min" class="form-control" value="18" min="13" max="65">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">{{ __('Max Age') }}</label>
                <input type="number" name="age_max" class="form-control" value="65" min="13" max="65">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">{{ __('Genders') }}</label>
                <select name="genders[]" class="form-select" multiple>
                    <option value="1">{{ __('Male') }}</option>
                    <option value="2">{{ __('Female') }}</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">{{ __('Countries') }} <span class="text-muted small">({{ __('ISO 2-letter codes, comma separated') }})</span></label>
                <input type="text" name="locations[]" class="form-control" placeholder="{{ __('e.g. IN, US, AE, GB') }}">
                <div class="form-text">{{ __('Leave blank for worldwide targeting.') }}</div>
            </div>
        </div>
    </div>
</div>

{{-- ── Step 5: Budget ───────────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-3 step-panel d-none" id="step4">
    <div class="card-body">
        <h6 class="fw-semibold mb-3">{{ __('Budget & Schedule') }}</h6>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-semibold">{{ __('Currency') }}</label>
                <select name="currency" class="form-select">
                    @foreach(['USD','EUR','GBP','INR','AED','SGD','AUD','CAD'] as $cur)
                    <option value="{{ $cur }}">{{ $cur }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">{{ __('Daily Budget') }}</label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" name="budget_daily" class="form-control" step="0.01" min="1" placeholder="10.00">
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">{{ __('Total Budget') }} <span class="text-muted small">({{ __('optional') }})</span></label>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" name="budget_total" class="form-control" step="0.01" min="0" placeholder="{{ __('No limit') }}">
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">{{ __('Bid Strategy') }}</label>
                <select name="bid_strategy" class="form-select">
                    <option value="lowest_cost">{{ __('Lowest Cost (auto)') }}</option>
                    <option value="target_cost">{{ __('Target Cost') }}</option>
                    <option value="bid_cap">{{ __('Bid Cap') }}</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">{{ __('Start Date') }}</label>
                <input type="datetime-local" name="start_at" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">{{ __('End Date') }} <span class="text-muted small">({{ __('optional') }})</span></label>
                <input type="datetime-local" name="end_at" class="form-control">
            </div>
        </div>
    </div>
</div>

{{-- ── Step 6: Review ───────────────────────────────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-3 step-panel d-none" id="step5">
    <div class="card-body">
        <h6 class="fw-semibold mb-3">{{ __('Review & Launch') }}</h6>
        <div id="reviewSummary" class="text-muted small">{{ __('Filling in summary...') }}</div>
        <div class="alert alert-info mt-3 small">
            <i class="bi bi-info-circle me-2"></i>
            {{ __('Your campaign will be saved as a draft. You can launch it from the campaign detail page or click Launch below.') }}
        </div>
    </div>
</div>

{{-- Navigation buttons --}}
<div class="d-flex justify-content-between mt-3">
    <button type="button" class="btn btn-outline-secondary" id="prevBtn" disabled onclick="changeStep(-1)">
        <i class="bi bi-arrow-left me-1"></i>{{ __('Previous') }}
    </button>
    <div>
        <button type="submit" name="action" value="draft" class="btn btn-outline-primary d-none" id="saveDraftBtn">
            <i class="bi bi-save me-1"></i>{{ __('Save Draft') }}
        </button>
        <button type="submit" name="action" value="launch" class="btn btn-success d-none" id="launchBtn">
            <i class="bi bi-play-fill me-1"></i>{{ __('Save & Launch') }}
        </button>
        <button type="button" class="btn btn-primary" id="nextBtn" onclick="changeStep(1)">
            {{ __('Next') }}<i class="bi bi-arrow-right ms-1"></i>
        </button>
    </div>
</div>

</form>
</div>
</div>

</x-layout-dashboard>

<style>
.objective-card.selected, .channel-card.selected { border-color: #0d6efd !important; background: rgba(13,110,253,.05); }
.format-tab { font-size: 12px; padding: 4px 10px; }
.btn-xs { font-size: 12px; padding: 2px 8px; }
.media-drop-zone:hover { background: #f0f4ff !important; }

/* Channel preview mockups */
.preview-phone { background:#1c1c1c; border-radius:36px; padding:18px 12px; max-width:280px; margin:0 auto; box-shadow:0 8px 32px rgba(0,0,0,.25); }
.preview-phone-screen { background:#fff; border-radius:22px; overflow:hidden; min-height:300px; }
.preview-wa-header { background:#075e54; color:#fff; padding:10px 14px; display:flex; align-items:center; gap:8px; }
.preview-wa-body { background:#e5ddd5; padding:12px; min-height:200px; }
.preview-wa-bubble { background:#fff; border-radius:8px; padding:10px 12px; max-width:85%; margin-left:auto; box-shadow:0 1px 2px rgba(0,0,0,.15); }
.preview-wa-cta { background:#128c7e; color:#fff; border:none; border-radius:0 0 8px 8px; width:100%; padding:8px; font-size:13px; text-align:center; }

.preview-fb-card { border:1px solid #ddd; border-radius:8px; overflow:hidden; background:#fff; font-family:sans-serif; }
.preview-fb-header { display:flex; align-items:center; gap:8px; padding:10px 12px; }
.preview-fb-avatar { width:36px; height:36px; border-radius:50%; background:#1877f2; color:#fff; display:flex; align-items:center; justify-content:center; font-size:14px; font-weight:700; flex-shrink:0; }
.preview-fb-img { width:100%; height:160px; object-fit:cover; background:#e4e6ea; display:flex; align-items:center; justify-content:center; }
.preview-fb-body { padding:10px 12px; }
.preview-fb-cta { margin:8px 12px 12px; background:#fff; border:1px solid #1877f2; color:#1877f2; border-radius:6px; padding:6px; text-align:center; font-size:13px; font-weight:600; }

.preview-ig-story { background:#000; border-radius:22px; overflow:hidden; max-width:200px; margin:0 auto; position:relative; height:360px; display:flex; flex-direction:column; }
.preview-ig-story-bg { flex:1; background:linear-gradient(135deg,#833ab4,#fd1d1d,#fcb045); display:flex; align-items:center; justify-content:center; }
.preview-ig-story-overlay { position:absolute; bottom:0; left:0; right:0; padding:12px; background:linear-gradient(transparent,rgba(0,0,0,.7)); color:#fff; }

.preview-li-card { border:1px solid #e0e0e0; border-radius:8px; overflow:hidden; background:#fff; font-family:sans-serif; }
.preview-li-header { display:flex; gap:8px; padding:12px; align-items:flex-start; }
.preview-li-avatar { width:40px; height:40px; border-radius:4px; background:#0a66c2; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:16px; flex-shrink:0; }
.preview-li-img { width:100%; height:150px; object-fit:cover; background:#e8e8e8; display:flex; align-items:center; justify-content:center; }
.preview-li-cta { margin:8px 12px 12px; background:#0a66c2; color:#fff; border-radius:16px; padding:5px 16px; font-size:13px; font-weight:600; display:inline-block; }

.preview-tg-bubble { background:#effdde; border-radius:12px 12px 0 12px; padding:10px 14px; max-width:85%; margin-left:auto; box-shadow:0 1px 2px rgba(0,0,0,.1); font-family:sans-serif; }
.preview-tg-btn { background:#5bb2f5; color:#fff; border-radius:8px; padding:6px 12px; text-align:center; font-size:13px; margin-top:6px; }

.preview-email { border:1px solid #ddd; border-radius:8px; overflow:hidden; background:#fff; font-family:sans-serif; }
.preview-email-header { background:#f5f5f5; padding:8px 12px; font-size:11px; color:#666; border-bottom:1px solid #ddd; }
.preview-email-body { padding:16px; }
.preview-email-cta { background:#0d6efd; color:#fff; border-radius:6px; padding:8px 20px; font-size:13px; font-weight:600; display:inline-block; margin-top:10px; }
</style>

<script>
let currentStep = 0;
const totalSteps = 6;
let activePreviewChannel = 'whatsapp';
let previewMediaUrl = '';

function changeStep(dir) {
    document.getElementById('step' + currentStep).classList.add('d-none');
    document.getElementById('stepCircle' + currentStep).classList.remove('bg-primary','text-white');
    document.getElementById('stepCircle' + currentStep).classList.add('bg-success','text-white');
    document.getElementById('stepLabel' + currentStep).classList.remove('text-primary');
    document.getElementById('stepLabel' + currentStep).classList.add('text-success');

    currentStep += dir;
    currentStep = Math.max(0, Math.min(totalSteps - 1, currentStep));

    document.getElementById('step' + currentStep).classList.remove('d-none');
    document.getElementById('stepCircle' + currentStep).classList.remove('bg-light','text-muted','border','bg-success','text-white');
    document.getElementById('stepCircle' + currentStep).classList.add('bg-primary','text-white');
    document.getElementById('stepLabel' + currentStep).classList.remove('text-muted','text-success');
    document.getElementById('stepLabel' + currentStep).classList.add('text-primary');

    document.getElementById('prevBtn').disabled = currentStep === 0;
    document.getElementById('nextBtn').classList.toggle('d-none', currentStep === totalSteps - 1);
    document.getElementById('saveDraftBtn').classList.toggle('d-none', currentStep !== totalSteps - 1);
    document.getElementById('launchBtn').classList.toggle('d-none', currentStep !== totalSteps - 1);

    if (currentStep === 2) renderPreview();
    if (currentStep === totalSteps - 1) buildReview();
}

// ── Objective cards ──
document.querySelectorAll('.objective-card').forEach(card => {
    card.addEventListener('click', function () {
        document.querySelectorAll('.objective-card').forEach(c => c.classList.remove('selected'));
        this.classList.add('selected');
        this.querySelector('.objective-radio').checked = true;
    });
});
document.querySelector('.objective-card')?.classList.add('selected');

// ── Channel cards ──
document.querySelectorAll('.channel-card').forEach(card => {
    card.addEventListener('click', function () {
        this.classList.toggle('selected');
        const cb = this.querySelector('.channel-checkbox');
        cb.checked = !cb.checked;
    });
});

// ── Format tabs ──
document.querySelectorAll('.format-tab').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.format-tab').forEach(b => {
            b.classList.remove('btn-primary');
            b.classList.add('btn-outline-secondary');
        });
        this.classList.remove('btn-outline-secondary');
        this.classList.add('btn-primary');
        const fmt = this.dataset.format;
        document.getElementById('formatHidden').value = fmt;
        const isCarousel = fmt === 'carousel';
        document.getElementById('carouselSection').classList.toggle('d-none', !isCarousel);
        document.getElementById('mediaUploadSection').classList.toggle('d-none', isCarousel);
        renderPreview();
    });
});

// ── Char counters ──
document.getElementById('cHeadline')?.addEventListener('input', function () {
    document.getElementById('headlineCount').textContent = this.value.length;
    renderPreview();
});
document.getElementById('cBody')?.addEventListener('input', function () {
    document.getElementById('bodyCount').textContent = this.value.length;
    renderPreview();
});
document.getElementById('cCta')?.addEventListener('input', renderPreview);

// ── Media drag & drop ──
const mediaInput = document.getElementById('mediaInput');
const mediaThumbs = document.getElementById('mediaThumbs');
mediaInput?.addEventListener('change', function () {
    mediaThumbs.innerHTML = '';
    [...this.files].forEach(file => {
        const url = URL.createObjectURL(file);
        previewMediaUrl = url;
        const thumb = document.createElement('div');
        thumb.style.cssText = 'width:72px;height:72px;border-radius:6px;overflow:hidden;border:1px solid #ddd;flex-shrink:0';
        if (file.type.startsWith('image/')) {
            thumb.innerHTML = `<img src="${url}" style="width:100%;height:100%;object-fit:cover">`;
        } else {
            thumb.innerHTML = `<video src="${url}" style="width:100%;height:100%;object-fit:cover" muted></video>`;
        }
        mediaThumbs.appendChild(thumb);
    });
    renderPreview();
});

// ── Existing creative selector ──
document.getElementById('existingCreative')?.addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    const hasExisting = !!this.value;
    document.getElementById('newCreativeForm').style.opacity = hasExisting ? '0.4' : '1';
    document.getElementById('newCreativeForm').style.pointerEvents = hasExisting ? 'none' : '';
    if (hasExisting) {
        document.getElementById('cHeadline').value = opt.dataset.headline || '';
        document.getElementById('cBody').value = opt.dataset.body || '';
        document.getElementById('cCta').value = opt.dataset.cta || '';
        previewMediaUrl = opt.dataset.media || '';
    }
    renderPreview();
});

// ── Preview tabs ──
document.querySelectorAll('.preview-tab').forEach(tab => {
    tab.addEventListener('click', function () {
        document.querySelectorAll('.preview-tab').forEach(t => {
            t.classList.remove('btn-primary');
            t.classList.add('btn-outline-secondary');
        });
        this.classList.remove('btn-outline-secondary');
        this.classList.add('btn-primary');
        activePreviewChannel = this.dataset.channel;
        renderPreview();
    });
});

// ── Carousel builder ──
document.getElementById('addCard')?.addEventListener('click', function () {
    const tmpl = document.querySelector('.carousel-card').cloneNode(true);
    tmpl.querySelectorAll('input').forEach(i => i.value = '');
    document.getElementById('carouselCards').appendChild(tmpl);
});
document.getElementById('carouselCards')?.addEventListener('click', function (e) {
    if (e.target.closest('.remove-card')) {
        const cards = document.querySelectorAll('.carousel-card');
        if (cards.length > 1) e.target.closest('.carousel-card').remove();
    }
});

// ── Live preview renderer ──
function getPreviewData() {
    const hasExisting = !!document.getElementById('existingCreative')?.value;
    return {
        headline: document.getElementById('cHeadline')?.value || (hasExisting ? document.getElementById('existingCreative').options[document.getElementById('existingCreative').selectedIndex]?.dataset.headline : '') || 'Your Headline Here',
        body:     document.getElementById('cBody')?.value     || (hasExisting ? document.getElementById('existingCreative').options[document.getElementById('existingCreative').selectedIndex]?.dataset.body : '') || 'Your ad copy goes here...',
        cta:      document.getElementById('cCta')?.value      || (hasExisting ? document.getElementById('existingCreative').options[document.getElementById('existingCreative').selectedIndex]?.dataset.cta : '') || 'Learn More',
        media:    previewMediaUrl,
        format:   document.getElementById('formatHidden')?.value || 'image',
    };
}

function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

function mediaEl(url, height='160px') {
    if (!url) return `<div style="width:100%;height:${height};background:#e4e6ea;display:flex;align-items:center;justify-content:center;color:#999;font-size:22px"><i class="bi bi-image"></i></div>`;
    return `<img src="${esc(url)}" style="width:100%;height:${height};object-fit:cover" alt="">`;
}

function renderPreview() {
    const d = getPreviewData();
    const area = document.getElementById('previewArea');
    if (!area) return;

    let html = '';

    if (activePreviewChannel === 'whatsapp') {
        html = `<div class="preview-phone">
          <div class="preview-phone-screen">
            <div class="preview-wa-header">
              <div style="width:28px;height:28px;border-radius:50%;background:#25d366;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0">B</div>
              <div>
                <div style="font-size:13px;font-weight:600">Your Business</div>
                <div style="font-size:10px;opacity:.8">Sponsored</div>
              </div>
            </div>
            <div class="preview-wa-body">
              <div class="preview-wa-bubble">
                ${d.media ? `<img src="${esc(d.media)}" style="width:100%;border-radius:6px;margin-bottom:6px">` : ''}
                ${d.headline ? `<div style="font-weight:600;font-size:13px;margin-bottom:4px">${esc(d.headline)}</div>` : ''}
                <div style="font-size:12px;color:#333;line-height:1.4">${esc(d.body)}</div>
                ${d.cta ? `<div class="preview-wa-cta">${esc(d.cta)}</div>` : ''}
              </div>
            </div>
          </div>
        </div>`;
    }
    else if (activePreviewChannel === 'facebook') {
        html = `<div class="preview-fb-card">
          <div class="preview-fb-header">
            <div class="preview-fb-avatar">B</div>
            <div>
              <div style="font-size:13px;font-weight:600">Your Page</div>
              <div style="font-size:10px;color:#65676b">Sponsored · <i class="bi bi-globe2"></i></div>
            </div>
          </div>
          ${d.headline ? `<div style="padding:0 12px 6px;font-size:13px;color:#1c1e21;line-height:1.4">${esc(d.body)}</div>` : ''}
          ${mediaEl(d.media)}
          <div style="padding:8px 12px;background:#f0f2f5;border-top:1px solid #ddd">
            <div style="font-size:11px;color:#65676b;text-transform:uppercase;letter-spacing:.5px">yourwebsite.com</div>
            <div style="font-size:13px;font-weight:600">${esc(d.headline)}</div>
          </div>
          ${d.cta ? `<div class="preview-fb-cta">${esc(d.cta)}</div>` : ''}
        </div>`;
    }
    else if (activePreviewChannel === 'instagram') {
        if (d.format === 'story' || d.format === 'reel') {
            html = `<div class="preview-ig-story">
              <div style="padding:8px;z-index:2;position:relative">
                <div style="height:3px;background:rgba(255,255,255,.5);border-radius:2px;margin-bottom:8px"><div style="width:60%;height:100%;background:#fff;border-radius:2px"></div></div>
                <div style="display:flex;align-items:center;gap:6px">
                  <div style="width:24px;height:24px;border-radius:50%;background:#fff;border:1px solid rgba(255,255,255,.5)"></div>
                  <div style="color:#fff;font-size:11px;font-weight:600">yourbusiness</div>
                  <div style="color:rgba(255,255,255,.7);font-size:10px">Sponsored</div>
                </div>
              </div>
              <div class="preview-ig-story-bg">
                ${d.media ? `<img src="${esc(d.media)}" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover">` : '<i class="bi bi-film text-white fs-1"></i>'}
              </div>
              <div class="preview-ig-story-overlay">
                ${d.headline ? `<div style="font-size:12px;font-weight:600">${esc(d.headline)}</div>` : ''}
                ${d.cta ? `<div style="background:rgba(255,255,255,.25);border-radius:4px;padding:4px 8px;font-size:11px;text-align:center;margin-top:4px">↑ ${esc(d.cta)}</div>` : ''}
              </div>
            </div>`;
        } else {
            html = `<div style="background:#fff;border:1px solid #dbdbdb;border-radius:8px;overflow:hidden;font-family:sans-serif">
              <div style="display:flex;align-items:center;gap:8px;padding:8px 12px">
                <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(45deg,#f58529,#dd2a7b,#8134af);display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;font-weight:700">B</div>
                <div>
                  <div style="font-size:12px;font-weight:600">yourbusiness</div>
                  <div style="font-size:10px;color:#8e8e8e">Sponsored</div>
                </div>
              </div>
              ${mediaEl(d.media, '220px')}
              <div style="padding:8px 12px">
                <div style="font-size:12px;line-height:1.4"><strong>yourbusiness</strong> ${esc(d.body)}</div>
                ${d.cta ? `<div style="color:#0095f6;font-size:12px;margin-top:4px">${esc(d.cta)} →</div>` : ''}
              </div>
            </div>`;
        }
    }
    else if (activePreviewChannel === 'linkedin') {
        html = `<div class="preview-li-card">
          <div class="preview-li-header">
            <div class="preview-li-avatar">B</div>
            <div>
              <div style="font-size:13px;font-weight:600">Your Company</div>
              <div style="font-size:11px;color:#666">Promoted</div>
            </div>
          </div>
          <div style="padding:0 12px 8px;font-size:13px;color:#333;line-height:1.5">${esc(d.body)}</div>
          <div class="preview-li-img">${d.media ? `<img src="${esc(d.media)}" style="width:100%;height:100%;object-fit:cover">` : '<i class="bi bi-image fs-1 text-muted"></i>'}</div>
          ${d.headline ? `<div style="padding:8px 12px;font-size:13px;font-weight:600;background:#f3f2f0;border-top:1px solid #ddd">${esc(d.headline)}</div>` : ''}
          ${d.cta ? `<div style="padding:8px 12px"><span class="preview-li-cta">${esc(d.cta)}</span></div>` : ''}
        </div>`;
    }
    else if (activePreviewChannel === 'telegram') {
        html = `<div style="background:#0088cc;padding:8px 12px;color:#fff;font-size:12px;border-radius:8px 8px 0 0;display:flex;align-items:center;gap:6px">
          <i class="bi bi-telegram"></i> Your Channel
        </div>
        <div style="background:#f0f4f8;padding:12px;border-radius:0 0 8px 8px">
          <div class="preview-tg-bubble">
            ${d.media ? `<img src="${esc(d.media)}" style="width:100%;border-radius:6px;margin-bottom:6px">` : ''}
            ${d.headline ? `<div style="font-weight:600;font-size:13px;margin-bottom:4px">${esc(d.headline)}</div>` : ''}
            <div style="font-size:12px;color:#333;line-height:1.4">${esc(d.body)}</div>
            ${d.cta ? `<div class="preview-tg-btn">${esc(d.cta)}</div>` : ''}
            <div style="font-size:10px;color:#999;text-align:right;margin-top:4px">now · ✓✓</div>
          </div>
        </div>`;
    }
    else if (activePreviewChannel === 'email') {
        html = `<div class="preview-email">
          <div class="preview-email-header">
            <strong>From:</strong> Your Business &lt;hello@yourbusiness.com&gt;<br>
            <strong>Subject:</strong> ${esc(d.headline || '(no subject)')}
          </div>
          <div class="preview-email-body">
            ${d.media ? `<img src="${esc(d.media)}" style="width:100%;border-radius:6px;margin-bottom:12px">` : ''}
            ${d.headline ? `<h6 style="font-size:15px;font-weight:700;margin-bottom:8px">${esc(d.headline)}</h6>` : ''}
            <p style="font-size:13px;color:#555;line-height:1.6">${esc(d.body)}</p>
            ${d.cta ? `<a class="preview-email-cta">${esc(d.cta)}</a>` : ''}
          </div>
        </div>`;
    }

    area.innerHTML = html;
}

// ── Review ──
function buildReview() {
    const name = document.querySelector('[name="name"]')?.value || '—';
    const objective = document.querySelector('.objective-radio:checked')?.value || '—';
    const channels = [...document.querySelectorAll('.channel-checkbox:checked')].map(c => c.closest('label').querySelector('.small.fw-semibold')?.textContent.trim() || c.closest('label').querySelector('.small')?.textContent.trim()).join(', ') || '—';
    const budget = document.querySelector('[name="budget_daily"]')?.value;
    const startAt = document.querySelector('[name="start_at"]')?.value;
    const creative = document.getElementById('existingCreative')?.options[document.getElementById('existingCreative').selectedIndex]?.text || document.getElementById('cName')?.value || '—';
    document.getElementById('reviewSummary').innerHTML = `
        <div class="row g-2">
            <div class="col-md-6"><strong>{{ __('Name') }}:</strong> ${esc(name)}</div>
            <div class="col-md-6"><strong>{{ __('Objective') }}:</strong> ${esc(objective)}</div>
            <div class="col-12"><strong>{{ __('Channels') }}:</strong> ${esc(channels)}</div>
            <div class="col-md-6"><strong>{{ __('Creative') }}:</strong> ${esc(creative)}</div>
            <div class="col-md-6"><strong>{{ __('Daily Budget') }}:</strong> ${budget ? '$' + parseFloat(budget).toFixed(2) : '{{ __("Not set") }}'}</div>
            ${startAt ? `<div class="col-md-6"><strong>{{ __('Start') }}:</strong> ${esc(startAt)}</div>` : ''}
        </div>`;
}

// ── Serialize carousel before submit ──
document.getElementById('campaignForm')?.addEventListener('submit', function () {
    const cards = [...document.querySelectorAll('.carousel-card')].map(card => {
        const obj = {};
        card.querySelectorAll('[data-field]').forEach(i => obj[i.dataset.field] = i.value);
        return obj;
    });
    document.getElementById('carouselCardsJson').value = JSON.stringify(cards);
});

// Initial render
renderPreview();
</script>
