<x-layout-dashboard title="{{ __('Setup Meta Channel') }}">

<x-page-header title="{{ __('Setup Meta Channel') }}"
    subtitle="{{ __('Select your Ad Account and Facebook Page to complete the connection') }}"
    :breadcrumb="[__('Ads Manager'), __('Channels'), __('Meta Setup')]" />

<div class="row justify-content-center">
<div class="col-lg-7">

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-success bg-opacity-10 border-0">
        <i class="bi bi-check-circle-fill text-success me-2"></i>
        <span class="fw-semibold text-success">{{ __('Authorised as') }}: {{ $userName }}</span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('ads.oauth.meta.setup') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-semibold">{{ __('Channel Type') }} <span class="text-danger">*</span></label>
                <select name="channel_type" class="form-select" required>
                    <option value="meta">Meta CTWA (Click-to-WhatsApp)</option>
                    <option value="facebook">Facebook Ads (Feed / Stories / Reels)</option>
                    <option value="instagram">Instagram (Feed / Stories / Reels)</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">{{ __('Channel Name') }} <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ $userName }}" required>
                <div class="form-text">{{ __('Internal label — shown in your channels list.') }}</div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">{{ __('Ad Account') }} <span class="text-danger">*</span></label>
                @if(empty($adAccounts))
                <div class="alert alert-warning small">
                    {{ __('No active ad accounts found for this Meta user. Make sure your account has access to a Meta Business Manager Ad Account with') }} <code>ads_management</code> {{ __('permission.') }}
                </div>
                <input type="text" name="meta_ad_account_id" class="form-control" placeholder="act_123456789" required>
                @else
                <select name="meta_ad_account_id" class="form-select" required>
                    @foreach($adAccounts as $account)
                    <option value="{{ $account['id'] }}">
                        {{ $account['name'] }} — {{ $account['id'] }} ({{ $account['currency'] ?? '' }})
                    </option>
                    @endforeach
                </select>
                @endif
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">{{ __('Facebook Page') }} <span class="text-muted small">({{ __('required for ad creatives') }})</span></label>
                @if(empty($pages))
                <div class="alert alert-info small mb-2">
                    {{ __('No pages found. You can add the Page ID manually.') }}
                </div>
                <input type="text" name="meta_page_id" class="form-control" placeholder="{{ __('Page ID') }}">
                @else
                <select name="meta_page_id" class="form-select" id="pageSelect">
                    <option value="">{{ __('— Select a Page —') }}</option>
                    @foreach($pages as $page)
                    <option value="{{ $page['id'] }}"
                            data-ig="{{ $page['instagram_business_account']['id'] ?? '' }}">
                        {{ $page['name'] }} ({{ $page['id'] }})
                    </option>
                    @endforeach
                </select>
                @endif
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">{{ __('Instagram Business Account ID') }} <span class="text-muted small">({{ __('auto-detected from page') }})</span></label>
                <input type="text" name="meta_ig_account_id" class="form-control" id="igAccountId" placeholder="{{ __('Auto-filled when page is selected') }}">
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check2 me-1"></i>{{ __('Complete Setup') }}
                </button>
                <a href="{{ route('ads.channels.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h6 class="fw-semibold mb-2">{{ __('Callback URL') }}</h6>
        <p class="text-muted small mb-2">{{ __('Register this exact URL in your Meta App Dashboard under') }} <strong>Facebook Login → Valid OAuth Redirect URIs</strong>:</p>
        <div class="input-group">
            <input type="text" class="form-control font-monospace small" readonly
                   value="{{ url('/ads/oauth/meta/callback') }}" id="callbackUrl">
            <button class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('callbackUrl').value);toastr.success('{{ __("Copied") }}')">
                <i class="bi bi-clipboard"></i>
            </button>
        </div>
    </div>
</div>

</div>
</div>

</x-layout-dashboard>

<script>
document.getElementById('pageSelect')?.addEventListener('change', function () {
    const igId = this.options[this.selectedIndex]?.dataset.ig ?? '';
    document.getElementById('igAccountId').value = igId;
});
</script>
