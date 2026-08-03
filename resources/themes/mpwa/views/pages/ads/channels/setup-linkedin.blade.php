<x-layout-dashboard title="{{ __('Setup LinkedIn Channel') }}">

<x-page-header title="{{ __('Setup LinkedIn Channel') }}"
    subtitle="{{ __('Select your LinkedIn Company Page to complete the connection') }}"
    :breadcrumb="[__('Ads Manager'), __('Channels'), __('LinkedIn Setup')]" />

<div class="row justify-content-center">
<div class="col-lg-7">

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-primary bg-opacity-10 border-0">
        <i class="bi bi-check-circle-fill text-primary me-2"></i>
        <span class="fw-semibold text-primary">{{ __('Authorised as') }}: {{ $displayName }}</span>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('ads.oauth.linkedin.setup') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-semibold">{{ __('Channel Name') }} <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control"
                       value="{{ !empty($organizations) ? $organizations[0]['name'] : $displayName }}" required>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">{{ __('Company / Organization') }} <span class="text-danger">*</span></label>
                @if(empty($organizations))
                <div class="alert alert-warning small mb-2">
                    {{ __('No organizations found where you have ADMINISTRATOR role. Make sure your LinkedIn account is a Company Page admin with') }}
                    <code>rw_organization_admin</code> {{ __('permission.') }}
                </div>
                <input type="text" name="organization_id" class="form-control"
                       placeholder="urn:li:organization:XXXXXXX {{ __('or numeric ID') }}" required>
                @else
                <select name="organization_id" class="form-select" id="orgSelect" required>
                    @foreach($organizations as $org)
                    <option value="{{ $org['id'] }}" data-name="{{ $org['name'] }}">
                        {{ $org['name'] }} — {{ $org['id'] }} ({{ $org['role'] }})
                    </option>
                    @endforeach
                </select>
                @endif
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
        <p class="text-muted small mb-2">{{ __('Register this exact URL in your LinkedIn Developer App under') }} <strong>Auth → Authorized redirect URLs</strong>:</p>
        <div class="input-group">
            <input type="text" class="form-control font-monospace small" readonly
                   value="{{ url('/ads/oauth/linkedin/callback') }}" id="callbackUrl">
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
document.getElementById('orgSelect')?.addEventListener('change', function () {
    const nameInput = document.querySelector('[name="name"]');
    if (nameInput) nameInput.value = this.options[this.selectedIndex]?.dataset.name ?? '';
});
</script>
