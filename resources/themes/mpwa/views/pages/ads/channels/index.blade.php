<x-layout-dashboard title="{{ __('Ad Channels') }}">
@if (session()->has('alert'))
<x-alert>
    @slot('type', session('alert')['type'])
    @slot('msg', session('alert')['msg'])
</x-alert>
@endif

<x-page-header title="{{ __('Ad Channels') }}"
    subtitle="{{ __('Connect your advertising accounts to run multi-channel campaigns') }}"
    :breadcrumb="[__('Ads Manager'), __('Channels')]" />

<div class="row g-3 mb-4">
    @php
        $channelDefs = [
            ['type' => 'meta',      'label' => 'Meta CTWA',  'desc' => 'Click-to-WhatsApp ads via Meta Ads API',       'icon' => 'bi-whatsapp',     'color' => 'success'],
            ['type' => 'facebook',  'label' => 'Facebook',   'desc' => 'Feed, Stories & Reels sponsored ads',           'icon' => 'bi-facebook',     'color' => 'primary'],
            ['type' => 'instagram', 'label' => 'Instagram',  'desc' => 'Feed posts, Stories, Reels & sponsored content','icon' => 'bi-instagram',    'color' => 'danger'],
            ['type' => 'linkedin',  'label' => 'LinkedIn',   'desc' => 'Company page posts & Sponsored Content',        'icon' => 'bi-linkedin',     'color' => 'primary'],
            ['type' => 'telegram',  'label' => 'Telegram',   'desc' => 'Channel posts & bot message blasts',            'icon' => 'bi-telegram',     'color' => 'info'],
            ['type' => 'email',     'label' => 'Email',      'desc' => 'Bulk email campaigns with link tracking',       'icon' => 'bi-envelope-fill','color' => 'warning'],
        ];
    @endphp

    @foreach($channelDefs as $def)
    @php $existing = $channels->where('type', $def['type'])->first(); @endphp
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="rounded-3 p-2 bg-{{ $def['color'] }} bg-opacity-10 d-flex align-items-center justify-content-center" style="width:48px;height:48px">
                        <i class="bi {{ $def['icon'] }} text-{{ $def['color'] }} fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-semibold">{{ $def['label'] }}</div>
                        <div class="text-muted small">{{ $def['desc'] }}</div>
                    </div>
                </div>

                @if($existing)
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge bg-{{ $existing->status === 'active' ? 'success' : ($existing->status === 'error' ? 'danger' : 'secondary') }} bg-opacity-15
                        text-{{ $existing->status === 'active' ? 'success' : ($existing->status === 'error' ? 'danger' : 'secondary') }} border" style="border-color:currentColor!important">
                        {{ ucfirst($existing->status) }}
                    </span>
                    <span class="small text-muted text-truncate">{{ $existing->name }}</span>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-primary flex-fill" onclick="verifyChannel({{ $existing->id }})">
                        <i class="bi bi-shield-check me-1"></i>{{ __('Verify') }}
                    </button>
                    <form method="POST" action="{{ route('ads.channels.destroy', $existing) }}" onsubmit="return confirm('{{ __('Disconnect this channel?') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </form>
                </div>
                @else
                @if(in_array($def['type'], ['meta', 'facebook', 'instagram']))
                {{-- OAuth flow for Meta family --}}
                <a href="{{ route('ads.oauth.meta.redirect') }}" class="btn btn-sm btn-primary w-100 mb-2">
                    <i class="bi bi-box-arrow-in-right me-1"></i>{{ __('Connect with Meta OAuth') }}
                </a>
                <button class="btn btn-sm btn-outline-secondary w-100" data-bs-toggle="modal" data-bs-target="#modal-{{ $def['type'] }}">
                    <i class="bi bi-key me-1"></i>{{ __('Enter token manually') }}
                </button>
                @elseif($def['type'] === 'linkedin')
                {{-- OAuth flow for LinkedIn --}}
                <a href="{{ route('ads.oauth.linkedin.redirect') }}" class="btn btn-sm btn-primary w-100 mb-2">
                    <i class="bi bi-linkedin me-1"></i>{{ __('Connect with LinkedIn OAuth') }}
                </a>
                <button class="btn btn-sm btn-outline-secondary w-100" data-bs-toggle="modal" data-bs-target="#modal-{{ $def['type'] }}">
                    <i class="bi bi-key me-1"></i>{{ __('Enter token manually') }}
                </button>
                @else
                <button class="btn btn-sm btn-primary w-100" data-bs-toggle="modal" data-bs-target="#modal-{{ $def['type'] }}">
                    <i class="bi bi-plus-lg me-1"></i>{{ __('Connect') }}
                </button>
                @endif
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Connected channels table --}}
@if($channels->isNotEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent border-0">
        <span class="fw-semibold">{{ __('Connected Accounts') }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">{{ __('Channel') }}</th>
                        <th>{{ __('Account Name') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Placements') }}</th>
                        <th>{{ __('Last Verified') }}</th>
                        <th class="pe-3">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($channels as $ch)
                <tr>
                    <td class="ps-3">
                        <i class="bi {{ $ch->typeIcon() }} text-{{ $ch->typeColor() }} fs-5 me-1"></i>
                        <span class="small fw-semibold">{{ $ch->typeLabel() }}</span>
                    </td>
                    <td>
                        <div class="small fw-semibold">{{ $ch->name }}</div>
                        @php $meta = $ch->metadata ?? []; @endphp
                        @if(!empty($meta['account_name']))
                        <div class="text-muted" style="font-size:11px">{{ $meta['account_name'] }}</div>
                        @elseif(!empty($meta['org_name']))
                        <div class="text-muted" style="font-size:11px">{{ $meta['org_name'] }}</div>
                        @elseif(!empty($meta['channel_title']))
                        <div class="text-muted" style="font-size:11px">{{ $meta['channel_title'] }}
                            @if(!empty($meta['member_count'])) · {{ number_format($meta['member_count']) }} {{ __('members') }} @endif
                        </div>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-{{ $ch->status === 'active' ? 'success' : ($ch->status === 'error' ? 'danger' : 'secondary') }} bg-opacity-15
                            text-{{ $ch->status === 'active' ? 'success' : ($ch->status === 'error' ? 'danger' : 'secondary') }}">
                            {{ ucfirst($ch->status) }}
                        </span>
                    </td>
                    <td class="small">{{ $ch->placements_count ?? 0 }}</td>
                    <td class="small text-muted">{{ $ch->last_verified_at?->format('d M Y, H:i') ?? '—' }}</td>
                    <td class="pe-3">
                        <button class="btn btn-sm btn-outline-primary me-1" onclick="verifyChannel({{ $ch->id }})" title="{{ __('Verify') }}">
                            <i class="bi bi-shield-check"></i>
                        </button>
                        <form method="POST" action="{{ route('ads.channels.destroy', $ch) }}" class="d-inline" onsubmit="return confirm('{{ __('Disconnect?') }}')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Disconnect') }}">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- ── Connect Modals ────────────────────────────────────────────────────────── --}}

{{-- Meta / Facebook / Instagram Modal --}}
@foreach(['meta' => 'Meta CTWA', 'facebook' => 'Facebook', 'instagram' => 'Instagram'] as $type => $label)
<div class="modal fade" id="modal-{{ $type }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('ads.channels.store') }}">
                @csrf
                <input type="hidden" name="type" value="{{ $type }}">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Connect') }} {{ $label }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small mb-3">
                        {{ __('Requires a Meta App with') }} <code>ads_management</code>, <code>ads_read</code> {{ __('permissions and a Business Manager Ad Account.') }}
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">{{ __('Account Name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="{{ __('e.g. My Business') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('Access Token') }} <span class="text-danger">*</span></label>
                            <input type="password" name="meta_access_token" class="form-control" placeholder="EAAxx..." required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('Ad Account ID') }} <span class="text-danger">*</span></label>
                            <input type="text" name="meta_ad_account_id" class="form-control" placeholder="act_123456789" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('Page ID') }}</label>
                            <input type="text" name="meta_page_id" class="form-control" placeholder="123456789">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('Instagram Business Account ID') }}</label>
                            <input type="text" name="meta_ig_account_id" class="form-control" placeholder="17841xxxxxx">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('App ID') }}</label>
                            <input type="text" name="meta_app_id" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('App Secret') }}</label>
                            <input type="password" name="meta_app_secret" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save & Connect') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

{{-- LinkedIn Modal --}}
<div class="modal fade" id="modal-linkedin" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('ads.channels.store') }}">
                @csrf
                <input type="hidden" name="type" value="linkedin">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Connect LinkedIn') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small mb-3">
                        {{ __('Requires a LinkedIn Developer App with') }} <code>rw_organization_admin</code> {{ __('and') }} <code>r_organization_social</code> {{ __('permissions.') }}
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('Account Name') }}</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('Access Token') }}</label>
                        <input type="password" name="linkedin_access_token" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('Organization ID') }}</label>
                        <input type="text" name="linkedin_organization_id" class="form-control" placeholder="urn:li:organization:XXXXXXX" required>
                        <div class="form-text">{{ __('Found in your LinkedIn Company Page URL or via the API.') }}</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save & Connect') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Telegram Modal --}}
<div class="modal fade" id="modal-telegram" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('ads.channels.store') }}">
                @csrf
                <input type="hidden" name="type" value="telegram">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Connect Telegram') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small mb-3">
                        {{ __('Create a bot via @BotFather on Telegram, then add it as admin to your channel.') }}
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('Account Name') }}</label>
                        <input type="text" name="name" class="form-control" placeholder="{{ __('My Telegram Channel') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('Bot Token') }}</label>
                        <input type="password" name="telegram_bot_token" class="form-control" placeholder="123456:ABC-DEF..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('Channel Username') }}</label>
                        <input type="text" name="telegram_channel_username" class="form-control" placeholder="@mychannel">
                        <div class="form-text">{{ __('Include the @ prefix, or use a numeric chat ID.') }}</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save & Connect') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Email Modal --}}
<div class="modal fade" id="modal-email" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('ads.channels.store') }}">
                @csrf
                <input type="hidden" name="type" value="email">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Connect Email') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('Account Name') }}</label>
                        <input type="text" name="name" class="form-control" placeholder="{{ __('e.g. Mailgun Production') }}" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('SMTP Host') }}</label>
                            <input type="text" name="email_host" class="form-control" placeholder="smtp.mailgun.org">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold">{{ __('Port') }}</label>
                            <input type="number" name="email_port" class="form-control" value="587">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">{{ __('Username') }}</label>
                            <input type="text" name="email_username" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('Password') }}</label>
                            <input type="password" name="email_password" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('API Key') }} <span class="text-muted small">({{ __('optional, for SendGrid/Mailgun API') }})</span></label>
                            <input type="password" name="email_api_key" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('From Address') }}</label>
                            <input type="email" name="email_from_address" class="form-control" placeholder="hello@yourdomain.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('From Name') }}</label>
                            <input type="text" name="email_from_name" class="form-control" placeholder="{{ __('My Company') }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save & Connect') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

</x-layout-dashboard>

<script>
function verifyChannel(id) {
    $.ajax({
        url: '{{ url("") }}/' + '{{ app()->getLocale() }}' + '/ads/channels/' + id + '/verify',
        type: 'POST',
        data: { _token: '{{ csrf_token() }}' },
        success: res => {
            res.error ? toastr.error(res.msg) : toastr.success(res.msg);
            if (!res.error) setTimeout(() => location.reload(), 1200);
        },
        error: () => toastr.error('{{ __("Something went wrong") }}'),
    });
}
</script>
