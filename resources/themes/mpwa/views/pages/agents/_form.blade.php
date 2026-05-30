<div class="mb-3">
    <label class="form-label fw-semibold small">{{ __('Name') }} <span class="text-danger">*</span></label>
    <input name="name" class="form-control form-control-sm" value="{{ old('name', $agent?->name) }}" required>
</div>
<div class="mb-3">
    <label class="form-label fw-semibold small">{{ __('Email') }}</label>
    <input name="email" type="email" class="form-control form-control-sm" value="{{ old('email', $agent?->email) }}">
</div>
<div class="row g-2">
    <div class="col-6 mb-3">
        <label class="form-label fw-semibold small">{{ __('Role') }} <span class="text-danger">*</span></label>
        <select name="role" class="form-select form-select-sm" required>
            <option value="agent"      {{ old('role', $agent?->role) === 'agent'      ? 'selected' : '' }}>{{ __('Agent') }}</option>
            <option value="supervisor" {{ old('role', $agent?->role) === 'supervisor' ? 'selected' : '' }}>{{ __('Supervisor') }}</option>
            <option value="admin"      {{ old('role', $agent?->role) === 'admin'      ? 'selected' : '' }}>{{ __('Admin') }}</option>
        </select>
    </div>
    <div class="col-6 mb-3">
        <label class="form-label fw-semibold small">{{ __('Team') }}</label>
        <select name="team_id" class="form-select form-select-sm">
            <option value="">{{ __('No team') }}</option>
            @foreach($teams as $team)
                <option value="{{ $team->id }}" {{ old('team_id', $agent?->team_id) == $team->id ? 'selected' : '' }}>
                    {{ $team->name }}
                </option>
            @endforeach
        </select>
    </div>
</div>
<div class="mb-3">
    <label class="form-label fw-semibold small">{{ __('Max Concurrent Chats') }}</label>
    <input name="max_concurrent_chats" type="number" min="1" max="100"
        class="form-control form-control-sm" value="{{ old('max_concurrent_chats', $agent?->max_concurrent_chats ?? 10) }}">
</div>
