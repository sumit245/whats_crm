<x-layout-auth>
    @slot('title', __('Invitation Expired'))

    <main class="authentication-content mt-5">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 col-lg-4 mx-auto text-center">
                    <div class="card shadow rounded-5 overflow-hidden">
                        <div class="card-body p-4 p-sm-5">
                            <span style="font-size:48px">⏰</span>
                            <h5 class="mt-3">{{ __('Invitation Expired') }}</h5>
                            <p class="text-muted">
                                {{ __('This invitation link has expired. Please ask your account owner to send a new one.') }}
                            </p>
                            <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm">
                                {{ __('Back to Login') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

</x-layout-auth>
