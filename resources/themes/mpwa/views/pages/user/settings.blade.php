<x-layout-dashboard title="{{ __('Setting') }}">

    <x-page-header title="{{ __('Settings') }}"
        subtitle="{{ __('Manage your password, API key and two-factor authentication') }}"
        :breadcrumb="[__('User'), __('Settings')]" />

    @if (session()->has('alert'))
        <x-alert>
            @slot('type', session('alert')['type'])
            @slot('msg', session('alert')['msg'])
        </x-alert>
    @endif

    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">{{ __('Settings') }}</h5>

            <div class="card mb-3">
                <div class="card-body">
                    <form action="{{ route('generateNewApiKey') }}" method="POST">
                        @csrf
                        <div class="input-group">
                            <span class="input-group-text">{{ __('API Key') }}</span>
                            <input type="text" class="form-control" value="{{ Auth::user()->api_key }}" readonly>
                            <button type="submit" name="api_key" class="btn btn-primary">{{ __('Generate New') }}</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <form action="{{ route('changePassword') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label for="settingsCurrentPassword" class="form-label">{{ __('Current Password') }}</label>
                                    <input type="password" name="current"
                                        class="form-control {{ $errors->has('current') ? 'is-invalid' : '' }}"
                                        placeholder="●●●●●●●●">
                                    @if ($errors->has('current'))
                                        <div class="invalid-feedback">{{ $errors->first('current') }}</div>
                                    @endif
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">{{ __('New Password') }}</label>
                                    <input type="password" name="password"
                                        class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                                        placeholder="●●●●●●●●">
                                    @if ($errors->has('password'))
                                        <div class="invalid-feedback">{{ $errors->first('password') }}</div>
                                    @endif
                                </div>
                                <div class="mb-3">
                                    <label for="settingsConfirmPassword" class="form-label">{{ __('Confirm Password') }}</label>
                                    <input type="password" name="password_confirmation" class="form-control"
                                        placeholder="●●●●●●●●">
                                </div>
                                <button type="submit" class="btn btn-primary">{{ __('Change Password') }}</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-body">
                            <form method="POST" action="{{ route('user.settings.2fa') }}">
                                @csrf
                                @if (auth()->user()->two_factor_enabled)
                                    <button type="submit" name="action" class="btn btn-danger w-100" value="disable">{{ __('Disable Authenticator 2FA?') }}</button>
                                @else
                                    <button type="submit" name="action" class="btn btn-primary w-100" value="enable">{{ __('Enable Authenticator 2FA?') }}</button>
                                @endif
                            </form>

                            @if (auth()->user()->two_factor_enabled)
                                <div class="mt-3 p-3 rounded" style="background:var(--dnd-brand-subtle)">
                                    <h6 class="mb-2">{{ __('Recovery Codes') }}</h6>
                                    <p class="small text-muted mb-2">{{ __('You can use Recovery Codes if you accidentally delete the Google Authenticator app or lose your phone. Use these codes when logging in instead of the app') }}</p>
                                    <div class="row g-2">
                                        @foreach (json_decode(auth()->user()->recovery_codes) as $code)
                                            <div class="col-4"><code class="small">{{ $code }}</code></div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <form method="POST" action="{{ route('deleteHistory') }}">
                                @csrf
                                <label for="delete_history" class="form-label">{{ __('Automatically delete message history:') }}</label>
                                <div class="d-flex flex-wrap gap-2 align-items-center">
                                    <select name="delete_history" class="form-select" style="max-width:120px">
                                        <option value="0" @selected(auth()->user()->delete_history == 0)>{{ __("Don't Delete") }}</option>
                                        @foreach (range(1, 30) as $number)
                                            <option value="{{ $number }}" @selected($number == auth()->user()->delete_history)>{{ $number }}</option>
                                        @endforeach
                                    </select>
                                    <span class="text-muted small">{{ __('In Days') }}</span>
                                    <button type="submit" class="btn btn-primary ms-auto">{{ __('Save') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-layout-dashboard>
