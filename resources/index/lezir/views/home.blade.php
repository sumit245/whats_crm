<x-index-layout>

    {{-- ════════════════════════════════
         HERO
         ════════════════════════════════ --}}
    <section class="hero-1-bg" id="home">
        <div class="container">
            <div class="row align-items-center justify-content-between g-5">

                {{-- Left: copy --}}
                <div class="col-lg-6" data-reveal data-reveal-hero>
                    <div class="hero-badge">
                        <span class="badge-dot"></span>
                        WhatsApp Marketing Platform
                    </div>
                    <h1 class="hero-1-title fw-bold mb-4">{{ __index('BOOST_MPWA') }}</h1>
                    <p class="text-muted mb-4">{{ __index('BOOST_MPWA_MSG') }}</p>

                    <div class="d-flex align-items-center gap-3 flex-wrap mb-2">
                        <a href="#login" class="btn-premium" data-bs-toggle="modal" data-bs-target="#exampleModalCenter">
                            {{ __index('SIGN_IN') }}
                            <span class="btn-icon-wrap">↗</span>
                        </a>
                        <a href="#pricing" data-scroll class="btn btn-outline-primary">
                            {{ __index('PRICING') }}
                        </a>
                    </div>

                    <div class="hero-stats">
                        <div class="hero-stat">
                            <span class="hero-stat-value">50k+</span>
                            <span class="hero-stat-label">Messages daily</span>
                        </div>
                        <div class="hero-stat-divider"></div>
                        <div class="hero-stat">
                            <span class="hero-stat-value">99.3%</span>
                            <span class="hero-stat-label">Delivery rate</span>
                        </div>
                        <div class="hero-stat-divider"></div>
                        <div class="hero-stat">
                            <span class="hero-stat-value">24/7</span>
                            <span class="hero-stat-label">Auto-reply</span>
                        </div>
                    </div>
                </div>

                {{-- Right: hero image in double-bezel frame --}}
                <div class="col-lg-6 col-md-10" data-reveal data-reveal-hero data-reveal-delay="120">
                    <div class="hero-image-shell animate-float">
                        <div class="hero-image-core">
                            <img src="{{ asset_index('images/hero-1-img.png') }}"
                                 alt="WhatsApp marketing dashboard preview"
                                 class="img-fluid">
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- ════════════════════════════════
         FEATURES — Asymmetric Bento Grid
         ════════════════════════════════ --}}
    <section class="section" id="features">
        <div class="container">

            <div class="section-header" data-reveal>
                <div class="section-eyebrow">Platform capabilities</div>
                <h2 class="title">Everything you need to scale</h2>
                <p>From AI-powered auto-replies to multi-stage campaigns — all the tools your business needs to stay connected.</p>
            </div>

            <div class="bento-grid">

                {{-- Video cell — spans both rows --}}
                <div class="bento-video-cell" data-reveal>
                    <div class="bento-video-shell">
                        <div class="bento-video-core">
                            <video autoplay muted loop playsinline>
                                <source src="{{ asset_index('images/autoreply.webm') }}" type="video/webm">
                            </video>
                        </div>
                    </div>
                </div>

                {{-- AI Reply --}}
                <div class="db-shell" data-reveal>
                    <div class="wc-box wc-box-primary">
                        <div class="wc-box-icon">
                            <i class="mdi mdi-robot-outline"></i>
                        </div>
                        <h5 class="wc-title">{{ __index('AI_REPLY') }}</h5>
                        <p class="wc-subtitle">{{ __index('AI_REPLY_MSG') }}</p>
                    </div>
                </div>

                {{-- Template Messaging --}}
                <div class="db-shell" data-reveal>
                    <div class="wc-box wc-box-primary">
                        <div class="wc-box-icon">
                            <i class="mdi mdi-layers-outline"></i>
                        </div>
                        <h5 class="wc-title">{{ __index('TEMPLATE_MESSAGING') }}</h5>
                        <p class="wc-subtitle">{{ __index('TEMPLATE_MESSAGING_MSG') }}</p>
                    </div>
                </div>

                {{-- Auto Reply --}}
                <div class="db-shell" data-reveal>
                    <div class="wc-box wc-box-primary">
                        <div class="wc-box-icon">
                            <i class="mdi mdi-lightning-bolt-outline"></i>
                        </div>
                        <h5 class="wc-title">{{ __index('AUTO_REPLY') }}</h5>
                        <p class="wc-subtitle">{{ __index('AUTO_REPLY_MSG') }}</p>
                    </div>
                </div>

                {{-- Actions Buttons --}}
                <div class="db-shell" data-reveal>
                    <div class="wc-box wc-box-primary">
                        <div class="wc-box-icon">
                            <i class="mdi mdi-gesture-tap-button"></i>
                        </div>
                        <h5 class="wc-title">{{ __index('ACTIONS_BUTTONS') }}</h5>
                        <p class="wc-subtitle">{{ __index('ACTIONS_BUTTONS_MSG') }}</p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    {{-- ════════════════════════════════
         PRICING
         ════════════════════════════════ --}}
    <section class="section" id="pricing" style="background: var(--clr-bg-muted);">
        <div class="container">

            <div class="section-header" data-reveal>
                <div class="section-eyebrow">Pricing</div>
                <h2 class="title">{{ __index('CHOOSE_PLAN') }}</h2>
                <p>{{ __index('CHOOSE_PLAN_MSG') }}</p>
            </div>

            <div class="row justify-content-center">
                @foreach($plans ?? [] as $index => $plan)
                    @if($index != 0 && $index % 3 == 0)
                        </div>
                        <div class="row justify-content-center mt-4">
                    @endif
                    <div class="col-lg-4 {{ count($plans) > 3 && $index >= 3 ? 'col-lg-4 mx-auto' : '' }}"
                         data-reveal data-reveal-delay="{{ $index * 80 }}">
                        <div class="pricing-box rounded text-center {{ $plan->is_recommended == 1 ? 'active' : '' }} p-4">
                            <div class="pricing-icon-bg my-4">
                                <i class="mdi mdi-account-group"></i>
                            </div>
                            <h4 class="title mb-3">{{ $plan->title }}</h4>
                            <h1 class="fw-bold mb-0">
                                <sup class="h4 me-1 fw-bold">{{ $plan->symbol }}</sup>{{ number_format($plan->price) }}
                            </h1>
                            <p class="text-muted mb-3">{{ __index('USER_MONTH') }}</p>
                            <ul class="list-unstyled pricing-item text-start mb-4 px-0">
                                @foreach($plan->data ?? [] as $key => $data)
                                <li class="d-flex align-items-center gap-2">
                                    <i class="mdi {{ $data == false ? 'mdi-cancel text-danger' : 'mdi-check-circle' }} flex-shrink-0"></i>
                                    {{ __index(strtoupper($key)) }}
                                    {{ ($key == 'messages_limit' || $key == 'device_limit') ? '('.number_format($data).')' : '' }}
                                </li>
                                @endforeach
                            </ul>
                            @if($plan->is_trial != 1)
                                <a href="{{ route('payments.checkout', $plan->id) }}"
                                   class="btn {{ $plan->is_recommended == 1 ? 'btn-primary' : 'btn-outline-primary' }} w-100">
                                    {{ __index('BUY_NOW') }}
                                </a>
                            @else
                                <a href="{{ route('payments.checkout', $plan->id) }}"
                                   class="btn {{ $plan->is_recommended == 1 ? 'btn-primary' : 'btn-outline-primary' }} w-100">
                                    {{ __index('BUY_NOW') }}
                                </a>
                                <a href="{{ route('payments.trial', $plan->id) }}"
                                   class="mt-2 btn {{ $plan->is_recommended == 1 ? 'btn-danger' : 'btn-outline-primary' }} w-100">
                                    {{ __index('TRIAL_DAYS', ['trial_days' => $plan->trial_days]) }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

        </div>
    </section>

    {{-- ════════════════════════════════
         CONTACT
         ════════════════════════════════ --}}
    <section class="section" id="contact">
        <div class="container">

            <div class="section-header" data-reveal>
                <div class="section-eyebrow">Get in touch</div>
                <h2 class="title">{{ __index('CONTACT_US') }}</h2>
                <p>{{ __index('CONTACT_US_MSG') }}</p>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-7 align-self-center" data-reveal>
                    <div class="custom-form mb-5 mb-lg-0">
                        <form method="post" name="myForm" onsubmit="return validateForm()">
                            <p id="error-msg"></p>
                            <div id="simple-msg"></div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">{{ __index('NAME') }}</label>
                                        <input name="name" id="name" type="text" class="form-control"
                                               placeholder="{{ __index('NAME') }}...">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">{{ __index('EMAIL') }}</label>
                                        <input name="email" id="email" type="email" class="form-control"
                                               placeholder="{{ __index('EMAIL') }}...">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="comments">{{ __index('MESSAGE') }}</label>
                                        <textarea name="comments" id="comments" rows="5" class="form-control"
                                                  placeholder="{{ __index('MESSAGE') }}..."></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <a class="btn-premium" href="#" id="submit"
                                       onclick="document.querySelector('[name=myForm]').dispatchEvent(new Event('submit',{bubbles:true,cancelable:true})); return false;">
                                        {{ __index('SEND_MESSAGE') }}
                                        <span class="btn-icon-wrap">
                                            <i class="icon" data-feather="send" style="width:14px;height:14px;"></i>
                                        </span>
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-lg-4 align-self-center ms-lg-4" data-reveal data-reveal-delay="120">
                    <div class="contact-detail mt-5 mt-lg-0">
                        <p>
                            <i class="icon-xs icon" data-feather="mail"></i>
                            <span>{{ __index('FOOTER_EMAIL') ?: 'hello@dashandots.com' }}</span>
                        </p>
                        <p>
                            <i class="icon-xs icon" data-feather="link"></i>
                            <span>{{ __index('FOOTER_LINK') }}</span>
                        </p>
                        <p>
                            <i class="icon-xs icon" data-feather="phone-call"></i>
                            <span dir="ltr">{{ __index('FOOTER_PHONE') }}</span>
                        </p>
                        <p>
                            <i class="icon-xs icon" data-feather="clock"></i>
                            <span>{{ __index('FOOTER_CLOCK') }}</span>
                        </p>
                        <p>
                            <i class="icon-xs icon" data-feather="map-pin"></i>
                            <span>{{ __index('FOOTER_MAP') }}</span>
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </section>

</x-index-layout>
