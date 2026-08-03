<x-layout-dashboard title="{{ __('Opt-in / Opt-out') }}">

@if(session('alert'))
    <div class="alert alert-{{ session('alert.type') }} alert-dismissible fade show" role="alert">
        {{ session('alert.msg') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="alert alert-info d-flex gap-2 align-items-start py-2 mb-3" role="alert">
    <i class="bi bi-info-circle-fill mt-1" style="flex-shrink:0"></i>
    <small>{{ __('When a contact sends your opt-out keyword (e.g. STOP), they are auto-added to the suppression list. When they send your opt-in keyword (e.g. JOIN), they are removed from suppression and added to your chosen phonebook.') }}</small>
</div>

<form method="POST" action="{{ route('optin.update') }}">
    @csrf
    <div class="row g-3">

        {{-- Left column: forms --}}
        <div class="col-12 col-lg-8">

            {{-- Opt-out --}}
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <span class="fw-semibold"><i class="bi bi-slash-circle-fill text-danger me-1"></i>{{ __('Opt-out') }}</span>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="opt_out_enabled" id="opt_out_enabled" value="1" {{ $settings->opt_out_enabled ? 'checked' : '' }}>
                        <label class="form-check-label small" for="opt_out_enabled">{{ __('Enabled') }}</label>
                    </div>
                </div>
                <div class="card-body pb-2">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold mb-1">{{ __('Keywords') }} <span class="text-danger">*</span></label>
                        <input type="text" name="opt_out_keywords" class="form-control form-control-sm @error('opt_out_keywords') is-invalid @enderror"
                               value="{{ implode(', ', $settings->opt_out_keywords ?: ['STOP', 'UNSUBSCRIBE', 'OPT OUT', 'OPTOUT', 'BERHENTI']) }}"
                               placeholder="STOP, UNSUBSCRIBE, OPT OUT">
                        <div class="form-text">{{ __('Comma-separated. Exact match, case-insensitive.') }}</div>
                        @error('opt_out_keywords')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold mb-1">{{ __('Auto-reply message') }}</label>
                        <textarea name="opt_out_reply" class="form-control form-control-sm" rows="2"
                                  placeholder="{{ __('e.g. You have been unsubscribed. Reply JOIN to re-subscribe.') }}">{{ old('opt_out_reply', $settings->opt_out_reply) }}</textarea>
                        <div class="form-text">{{ __('Leave blank to send no reply.') }}</div>
                    </div>
                </div>
            </div>

            {{-- Opt-in --}}
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <span class="fw-semibold"><i class="bi bi-toggle-on text-success me-1"></i>{{ __('Opt-in') }}</span>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" name="opt_in_enabled" id="opt_in_enabled" value="1" {{ $settings->opt_in_enabled ? 'checked' : '' }}>
                        <label class="form-check-label small" for="opt_in_enabled">{{ __('Enabled') }}</label>
                    </div>
                </div>
                <div class="card-body pb-2">
                    <div class="row g-3 mb-2">
                        <div class="col-sm-4">
                            <label class="form-label small fw-semibold mb-1">{{ __('Keyword') }}</label>
                            <input type="text" name="opt_in_keyword" class="form-control form-control-sm @error('opt_in_keyword') is-invalid @enderror"
                                   value="{{ old('opt_in_keyword', $settings->opt_in_keyword) }}"
                                   placeholder="JOIN" maxlength="64">
                            @error('opt_in_keyword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-8">
                            <label class="form-label small fw-semibold mb-1">{{ __('Add contact to phonebook') }}</label>
                            <select name="opt_in_phonebook_id" class="form-select form-select-sm">
                                <option value="">— {{ __('None') }} —</option>
                                @foreach($phonebooks as $pb)
                                    <option value="{{ $pb->id }}" {{ $settings->opt_in_phonebook_id == $pb->id ? 'selected' : '' }}>{{ $pb->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold mb-1">{{ __('Auto-reply message') }}</label>
                        <textarea name="opt_in_reply" class="form-control form-control-sm" rows="2"
                                  placeholder="{{ __('e.g. Welcome! Reply STOP at any time to unsubscribe.') }}">{{ old('opt_in_reply', $settings->opt_in_reply) }}</textarea>
                        <div class="form-text">{{ __('Leave blank to send no reply.') }}</div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-sm px-4">
                <i class="bi bi-save me-1"></i>{{ __('Save Settings') }}
            </button>
        </div>

        {{-- Right column: tips + wa.me links --}}
        <div class="col-12 col-lg-4">
            <div class="card border-0 bg-light mb-3">
                <div class="card-body py-3">
                    <div class="small fw-bold mb-2"><i class="bi bi-lightbulb-fill text-warning me-1"></i>{{ __('Tips') }}</div>
                    <ul class="small text-muted ps-3 mb-0" style="line-height:1.9">
                        <li>{{ __('WhatsApp requires opt-outs to be honored within 24 hours.') }}</li>
                        <li>{{ __('STOP is widely recognized across markets.') }}</li>
                        <li>{{ __('Opt-out replies are plain text — must be within a 24h session.') }}</li>
                        <li>{{ __('Opted-out contacts show as "Opt-Out" in the Suppression List.') }}</li>
                        <li>{{ __('Opt-in re-enrolls a contact even if they previously opted out.') }}</li>
                    </ul>
                </div>
            </div>

            @php $devices = auth()->user()->devices; @endphp
            @if($devices->isNotEmpty())
            <div class="card border-0 bg-light">
                <div class="card-body py-3">
                    <div class="small fw-bold mb-2"><i class="bi bi-link-45deg me-1"></i>{{ __('Shareable opt-in links') }}</div>
                    <p class="small text-muted mb-2">{{ __('Share with customers to open a pre-filled WhatsApp chat.') }}</p>
                    @foreach($devices as $dev)
                        @if($dev->number)
                        <div class="mb-2">
                            <div class="small text-muted mb-1">{{ $dev->name ?? $dev->number }}</div>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control form-control-sm font-monospace"
                                       id="walink-{{ $dev->id }}"
                                       value="https://wa.me/{{ $dev->number }}?text={{ urlencode($settings->opt_in_keyword ?: 'JOIN') }}"
                                       readonly>
                                <button type="button" class="btn btn-outline-secondary btn-sm copy-link" data-target="walink-{{ $dev->id }}">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
            @endif
        </div>

    </div>
</form>

<script>
document.querySelectorAll('.copy-link').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const input = document.getElementById(this.dataset.target);
        if (!input) return;
        navigator.clipboard.writeText(input.value).then(() => {
            btn.innerHTML = '<i class="bi bi-check2"></i>';
            setTimeout(() => { btn.innerHTML = '<i class="bi bi-clipboard"></i>'; }, 2000);
        });
    });
});
</script>

</x-layout-dashboard>
