<x-layout-auth>
    @slot('title', __('Accept Invitation'))

    <main class="authentication-content mt-5">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 col-lg-4 mx-auto">
                    <div class="card shadow rounded-5 overflow-hidden">
                        <div class="card-body p-4 p-sm-5">

                            @if(session('error'))
                                <div class="alert alert-danger py-2">{{ session('error') }}</div>
                            @endif
                            @if($errors->any())
                                <div class="alert alert-danger py-2">
                                    <ul class="mb-0 ps-3">
                                        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="text-center mb-4">
                                <span style="font-size:40px">👋</span>
                                <h5 class="card-title mt-2">{{ __('Welcome, :name!', ['name' => $invitation->agent->name]) }}</h5>
                                <p class="text-muted small">
                                    {{ __('Set your username and password to activate your agent account.') }}
                                </p>
                                @if($invitation->agent->team)
                                <span class="badge bg-success-subtle text-success">{{ $invitation->agent->team->name }}</span>
                                @endif
                                <span class="badge bg-secondary-subtle text-secondary ms-1">{{ ucfirst($invitation->agent->role) }}</span>
                            </div>

                            <form action="{{ route('agent.invite.accept', $invitation->token) }}" method="POST">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label">{{ __('Username') }} <span class="text-danger">*</span></label>
                                        <input type="text" name="username" class="form-control"
                                               value="{{ old('username') }}" required autofocus
                                               placeholder="{{ __('Choose a username') }}">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">{{ __('Email') }}</label>
                                        <input type="email" class="form-control"
                                               value="{{ $invitation->agent->email }}" readonly disabled>
                                        <div class="form-text">{{ __('Your login email (pre-filled from your agent profile).') }}</div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">{{ __('Password') }} <span class="text-danger">*</span></label>
                                        <input type="password" name="password" class="form-control" required
                                               placeholder="{{ __('At least 8 characters') }}">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">{{ __('Confirm Password') }} <span class="text-danger">*</span></label>
                                        <input type="password" name="password_confirmation" class="form-control" required>
                                    </div>
                                    <div class="col-12 mt-2">
                                        <button type="submit" class="btn btn-primary w-100">
                                            {{ __('Activate Account') }}
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    {{ __('Invitation expires') }}: {{ $invitation->expires_at->format('d M Y, H:i') }}
                                </small>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

</x-layout-auth>
