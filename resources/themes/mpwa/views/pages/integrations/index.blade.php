<x-layout-dashboard title="{{ __('Integrations') }}">

    <x-page-header title="{{ __('Integrations') }}"
        subtitle="{{ __('Connect your WhatsApp number to external apps, websites, and services.') }}"
        :breadcrumb="[__('Integrations')]" />

    <div class="row g-4">

        {{-- Custom App --}}
        <div class="col-md-6 col-xl-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body d-flex flex-column p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center bg-primary bg-opacity-10"
                             style="width:52px;height:52px">
                            <i class="bi bi-phone-fill fs-4 text-primary"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-semibold">{{ __('Custom App / API') }}</h6>
                            <span class="badge {{ $canCustomApp ? 'bg-success' : 'bg-secondary' }} bg-opacity-75 small">
                                {{ $canCustomApp ? __('Available') : __('Plan Required') }}
                            </span>
                        </div>
                    </div>
                    <p class="text-muted small mb-4">
                        {{ __('Connect your React Native, mobile, or server-side app. Send OTP messages, trigger templates, enroll contacts, and receive inbound messages via webhook.') }}
                    </p>
                    <div class="mt-auto">
                        @if($canCustomApp)
                            <a href="{{ route('integrations.custom-app') }}" class="btn btn-primary btn-sm">
                                {{ __('Configure') }} <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        @else
                            <button class="btn btn-outline-secondary btn-sm" disabled>{{ __('Upgrade to Unlock') }}</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Website Widget --}}
        <div class="col-md-6 col-xl-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body d-flex flex-column p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center bg-success bg-opacity-10"
                             style="width:52px;height:52px">
                            <i class="bi bi-globe2 fs-4 text-success"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-semibold">{{ __('Website Chat Widget') }}</h6>
                            <span class="badge {{ $canWidget ? 'bg-success' : 'bg-secondary' }} bg-opacity-75 small">
                                {{ $canWidget ? __('Available') : __('Plan Required') }}
                            </span>
                        </div>
                    </div>
                    <p class="text-muted small mb-4">
                        {{ __('Embed a floating WhatsApp button on any website — PHP, WordPress, React, or plain HTML. One script tag, zero setup. Messages arrive directly in your Live Chat inbox.') }}
                    </p>
                    <div class="mt-auto">
                        @if($canWidget)
                            <a href="{{ route('integrations.widget') }}" class="btn btn-success btn-sm">
                                {{ __('Build Widget') }} <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        @else
                            <button class="btn btn-outline-secondary btn-sm" disabled>{{ __('Upgrade to Unlock') }}</button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Coming Soon: Shopify --}}
        <div class="col-md-6 col-xl-4">
            <div class="card h-100 border-0 shadow-sm opacity-60">
                <div class="card-body d-flex flex-column p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center bg-secondary bg-opacity-10"
                             style="width:52px;height:52px">
                            <i class="bi bi-bag-fill fs-4 text-secondary"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-semibold text-muted">{{ __('Shopify') }}</h6>
                            <span class="badge bg-light text-secondary border small">{{ __('Coming Soon') }}</span>
                        </div>
                    </div>
                    <p class="text-muted small mb-4">
                        {{ __('Abandoned cart recovery, order confirmations, and customer re-engagement via WhatsApp.') }}
                    </p>
                    <div class="mt-auto">
                        <button class="btn btn-outline-secondary btn-sm" disabled>{{ __('Coming Soon') }}</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Coming Soon: WooCommerce --}}
        <div class="col-md-6 col-xl-4">
            <div class="card h-100 border-0 shadow-sm opacity-60">
                <div class="card-body d-flex flex-column p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center bg-secondary bg-opacity-10"
                             style="width:52px;height:52px">
                            <i class="bi bi-wordpress fs-4 text-secondary"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-semibold text-muted">{{ __('WooCommerce') }}</h6>
                            <span class="badge bg-light text-secondary border small">{{ __('Coming Soon') }}</span>
                        </div>
                    </div>
                    <p class="text-muted small mb-4">
                        {{ __('Trigger WhatsApp messages on order events, cart abandonment, and payment status changes.') }}
                    </p>
                    <div class="mt-auto">
                        <button class="btn btn-outline-secondary btn-sm" disabled>{{ __('Coming Soon') }}</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Coming Soon: Zapier --}}
        <div class="col-md-6 col-xl-4">
            <div class="card h-100 border-0 shadow-sm opacity-60">
                <div class="card-body d-flex flex-column p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center bg-secondary bg-opacity-10"
                             style="width:52px;height:52px">
                            <i class="bi bi-lightning-charge-fill fs-4 text-secondary"></i>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-semibold text-muted">{{ __('Zapier / Automation') }}</h6>
                            <span class="badge bg-light text-secondary border small">{{ __('Coming Soon') }}</span>
                        </div>
                    </div>
                    <p class="text-muted small mb-4">
                        {{ __('Connect 5,000+ apps via Zapier — trigger WhatsApp messages from CRMs, forms, Google Sheets, and more.') }}
                    </p>
                    <div class="mt-auto">
                        <button class="btn btn-outline-secondary btn-sm" disabled>{{ __('Coming Soon') }}</button>
                    </div>
                </div>
            </div>
        </div>

    </div>

</x-layout-dashboard>
