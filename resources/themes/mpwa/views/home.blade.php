<x-layout-dashboard title="{{ __('Home') }}">

    @if (session()->has('alert'))
        <x-alert>
            @slot('type', session('alert')['type'])
            @slot('msg', session('alert')['msg'])
        </x-alert>
    @endif

    <x-page-header title="{{ __('Dashboard') }}"
        subtitle="{{ __('Overview of your devices, campaigns and usage') }}" />

            {{-- Summary cards --}}
            <div class="row row-cols-1 row-cols-lg-2 row-cols-xl-4 g-3">
                <div class="col">
                    <div class="card rounded-4 h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-1">{{ __('Connected Devices') }}</p>
                                    <h4 class="mb-0">{{ $user->devices_count }}</h4>
                                    <p class="mb-0 mt-2 font-13">{{ __('Limit: :n', ['n' => $user->limit_device]) }}</p>
                                </div>
                                <div class="ms-auto widget-icon bg-primary text-white">
                                    <i class="bi bi-whatsapp"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card rounded-4 h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-1">{{ __('Blast / Bulk') }}</p>
                                    <p class="mb-0 badge bg-warning">{{ $user->blasts_pending }} {{ __('Pending') }}</p>
                                    <p class="mb-0 badge bg-success">{{ $user->blasts_success }} {{ __('Sent') }}</p>
                                    <p class="mb-0 badge bg-danger">{{ $user->blasts_failed }} {{ __('Failed') }}</p>
                                    <p class="mb-0 mt-2 font-13">{{ __(':n Campaigns', ['n' => $user->campaigns_count]) }}</p>
                                </div>
                                <div class="ms-auto widget-icon bg-success text-white">
                                    <i class="bi bi-broadcast"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card rounded-4 h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-1">{{ __('Subscription') }}</p>
                                    <h4 class="mb-0">{{ $user->subscription_status }}</h4>
                                    <p class="mb-0 mt-2 font-13">{{ __('Expires: ') }}{{ __($user->expired_subscription_status) }}</p>
                                </div>
                                <div class="ms-auto widget-icon bg-orange text-white">
                                    <i class="bi bi-credit-card"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card rounded-4 h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div>
                                    <p class="mb-1">{{ __('Messages Sent') }}</p>
                                    <h4 class="mb-0">{{ number_format($user->message_histories_count) }}</h4>
                                    <p class="mb-0 mt-2 font-13">
                                        {{ __('Remaining: :n', ['n' => $user->level === 'admin' ? '∞' : number_format($user->plan_data['messages_limit'] ?? 0)]) }}
                                    </p>
                                </div>
                                <div class="ms-auto widget-icon bg-info text-white">
                                    <i class="bi bi-chat-left-text"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Devices table --}}
            <div class="card mt-4">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <h5 class="mb-0">{{ __('WhatsApp Business Accounts') }}</h5>
                        <button type="button" class="btn btn-primary btn-sm ms-auto" data-bs-toggle="modal" data-bs-target="#addDevice">
                            <i class="bi bi-plus"></i> {{ __('Add Device') }}
                        </button>
                    </div>

                    <div class="table-responsive mt-3">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('Display Name') }}</th>
                                    <th>{{ __('Phone Number') }}</th>
                                    <th>{{ __('Quality') }}</th>
                                    <th>{{ __('Tier') }}</th>
                                    <th>{{ __('Messages Sent') }}</th>
                                    <th>{{ __('Webhook URL') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($numbers as $number)
                                    <tr>
                                        <td>
                                            <strong>{{ $number->meta_profile['verified_name'] ?? $number->body }}</strong>
                                            @if ($number->meta_profile['verified_name'] ?? null)
                                                <br><small class="text-muted">{{ $number->body }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $number->meta_profile['display_phone_number'] ?? $number->body }}</td>
                                        <td>
                                            @php $qc = match($number->quality_rating) { 'GREEN' => 'success', 'YELLOW' => 'warning', 'RED' => 'danger', default => 'secondary' }; @endphp
                                            <span class="badge bg-{{ $qc }}">{{ $number->quality_rating ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            @if ($number->messaging_tier)
                                                <span class="badge bg-info text-dark">{{ str_replace('TIER_', '', $number->messaging_tier) }}/day</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>{{ number_format($number->message_sent) }}</td>
                                        <td style="min-width:200px">
                                            <input type="text"
                                                class="form-control form-control-sm webhook-url-form"
                                                data-id="{{ $number->body }}"
                                                value="{{ $number->webhook }}"
                                                placeholder="{{ __('Optional webhook URL') }}">
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $number->status === 'Connected' ? 'success' : 'danger' }}">
                                                {{ __($number->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2 align-items-center">
                                                <a href="{{ route('meta.health') }}" class="btn btn-sm btn-outline-primary" title="{{ __('Health') }}">
                                                    <i class="bi bi-activity"></i>
                                                </a>
                                                <form action="{{ route('deleteDevice') }}" method="POST" onsubmit="return confirm('{{ __('Remove this device?') }}')">
                                                    @method('delete')
                                                    @csrf
                                                    <input name="deviceId" type="hidden" value="{{ $number->id }}">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            {{ __('No devices connected yet. Click "Add Device" to connect your WhatsApp Business Account.') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $numbers->links() }}
                </div>
            </div>

{{-- Add Device Modal --}}
<div class="modal fade" id="addDevice" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Connect WhatsApp Business Account') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('addDevice') }}" method="POST">
                @csrf
                <div class="modal-body">

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        {{ __('You need a Meta developer app with WhatsApp Business API enabled. Get your credentials from') }}
                        <a href="https://developers.facebook.com/apps" target="_blank">Meta Developer Portal</a>.
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('Display Label') }} <span class="text-danger">*</span></label>
                            <input type="text" name="sender" class="form-control" placeholder="e.g. My Business" required>
                            <div class="form-text">{{ __('A name to identify this account in the dashboard.') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('Phone Number ID') }} <span class="text-danger">*</span></label>
                            <input type="text" name="phone_number_id" class="form-control" placeholder="e.g. 123456789012345" required>
                            <div class="form-text">{{ __('Found in Meta Developer App → WhatsApp → API Setup') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('WhatsApp Business Account ID (WABA ID)') }} <span class="text-danger">*</span></label>
                            <input type="text" name="waba_id" class="form-control" placeholder="e.g. 987654321098765" required>
                            <div class="form-text">{{ __('Found in Meta Business Manager → WhatsApp Accounts') }}</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ __('Webhook Forwarding URL') }}</label>
                            <input type="url" name="urlwebhook" class="form-control" placeholder="https://your-app.com/webhook">
                            <div class="form-text">{{ __('Optional. Incoming messages will be forwarded here.') }}</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">{{ __('System User Access Token') }} <span class="text-danger">*</span></label>
                            <textarea name="access_token" class="form-control font-monospace" rows="3"
                                placeholder="EAAxxxxx..." required></textarea>
                            <div class="form-text">{{ __('Permanent System User token from Meta Business Manager. Never expires unless revoked.') }}</div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plug me-1"></i> {{ __('Verify & Connect') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var typingTimer;
var doneTypingInterval = 1000;

$('.webhook-url-form').on('keyup', function () {
    clearTimeout(typingTimer);
    let value = $(this).val();
    let number = $(this).data('id');
    typingTimer = setTimeout(function () {
        $.ajax({
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            url: '{{ route('setHook') }}',
            data: { number: number, webhook: value },
            dataType: 'json',
            success: () => toastr.success('{{ __("Webhook URL updated") }}'),
            error: (err) => toastr.error(err.responseJSON?.msg ?? 'Error'),
        });
    }, doneTypingInterval);
});
</script>

</x-layout-dashboard>
