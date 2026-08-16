<x-layout-dashboard title="{{ __('Chat Settings') }}">

<form method="POST" action="{{ route('chat.settings.update') }}">
@csrf

<div class="card border-0 shadow-sm">

    {{-- ── Header: nav, title, timezone, save — one row, nothing floating below it ── --}}
    <div class="card-header bg-transparent border-bottom d-flex flex-wrap align-items-center gap-2 py-2">
        <a href="{{ route('chat.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
        <h5 class="mb-0 fw-semibold me-auto">
            <i class="bi bi-gear-fill me-2 text-primary"></i>{{ __('Chat Settings') }}
        </h5>
        <label class="small text-muted mb-0 d-none d-sm-inline"><i class="bi bi-globe me-1"></i>{{ __('Timezone') }}</label>
        <select name="timezone" class="form-select form-select-sm" style="width:auto;min-width:200px">
            @foreach($timezones as $tz)
            <option value="{{ $tz }}" {{ $setting->timezone === $tz ? 'selected' : '' }}>{{ $tz }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-sm btn-primary">
            <i class="bi bi-check-lg me-1"></i>{{ __('Save Settings') }}
        </button>
    </div>

    {{-- ── Off-hours + Auto-resolve: two dense columns, one divider, no nested cards ── --}}
    <div class="row g-0 border-bottom">
        <div class="col-12 col-md-6 p-3 border-end">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="small fw-semibold"><i class="bi bi-moon-stars-fill me-1"></i>{{ __('Off-hours Auto-reply') }}</span>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" name="off_hours_enabled" id="offHoursToggle"
                           value="1" {{ $setting->off_hours_enabled ? 'checked' : '' }}
                           onchange="document.getElementById('offHoursBody').classList.toggle('d-none', !this.checked)">
                </div>
            </div>
            <div id="offHoursBody" class="{{ $setting->off_hours_enabled ? '' : 'd-none' }}">
                <textarea name="off_hours_message" class="form-control form-control-sm" rows="2"
                          placeholder="{{ __('e.g. Thanks for reaching out! We are currently outside our working hours. We will get back to you soon.') }}">{{ old('off_hours_message', $setting->off_hours_message) }}</textarea>
                <div class="form-text mb-0">{{ __('Sent once per contact per 4 hours. Plain text only.') }}</div>
            </div>
        </div>
        <div class="col-12 col-md-6 p-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="small fw-semibold"><i class="bi bi-check2-circle me-1"></i>{{ __('Auto-resolve Idle Conversations') }}</span>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" name="auto_resolve_enabled" id="autoResolveToggle"
                           value="1" {{ $setting->auto_resolve_enabled ? 'checked' : '' }}
                           onchange="document.getElementById('autoResolveBody').classList.toggle('d-none', !this.checked)">
                </div>
            </div>
            <div id="autoResolveBody" class="{{ $setting->auto_resolve_enabled ? '' : 'd-none' }}">
                <div class="input-group input-group-sm" style="max-width:220px">
                    <span class="input-group-text">{{ __('Resolve after') }}</span>
                    <input type="number" name="auto_resolve_hours" class="form-control"
                           value="{{ old('auto_resolve_hours', $setting->auto_resolve_hours) }}"
                           min="1" max="720">
                    <span class="input-group-text">{{ __('hrs idle') }}</span>
                </div>
                <div class="form-text mb-0">{{ __('Checked every 30 minutes; removed live from agent inboxes.') }}</div>
            </div>
        </div>
    </div>

    {{-- ── Working hours: full-width dense table, same container, no extra card ── --}}
    <div class="d-flex align-items-center justify-content-between px-3 pt-2">
        <span class="small fw-semibold"><i class="bi bi-clock-fill me-1"></i>{{ __('Working Hours') }}</span>
        <small class="text-muted"><i class="bi bi-info-circle me-1"></i>{{ __('Disabled days are treated as closed.') }}</small>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0" style="font-size:0.85rem">
            <thead class="table-light">
                <tr>
                    <th class="ps-3" style="width:40px">{{ __('On') }}</th>
                    <th style="width:110px">{{ __('Day') }}</th>
                    <th>{{ __('From') }}</th>
                    <th>{{ __('To') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($days as $key => $label)
                @php $slot = $hours[$key] ?? ['enabled' => false, 'start' => '09:00', 'end' => '18:00']; @endphp
                <tr>
                    <td class="ps-3">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input day-toggle" type="checkbox"
                                   name="hours_{{ $key }}_enabled"
                                   id="day_{{ $key }}"
                                   value="1"
                                   {{ !empty($slot['enabled']) ? 'checked' : '' }}
                                   onchange="toggleDay('{{ $key }}', this.checked)">
                        </div>
                    </td>
                    <td>
                        <label for="day_{{ $key }}" class="mb-0 fw-semibold">{{ $label }}</label>
                    </td>
                    <td>
                        <input type="time" name="hours_{{ $key }}_start"
                               id="start_{{ $key }}"
                               class="form-control form-control-sm"
                               value="{{ $slot['start'] ?? '09:00' }}"
                               style="max-width:120px"
                               {{ empty($slot['enabled']) ? 'disabled' : '' }}>
                    </td>
                    <td>
                        <input type="time" name="hours_{{ $key }}_end"
                               id="end_{{ $key }}"
                               class="form-control form-control-sm"
                               value="{{ $slot['end'] ?? '18:00' }}"
                               style="max-width:120px"
                               {{ empty($slot['enabled']) ? 'disabled' : '' }}>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>
</form>

<script>
function toggleDay(key, enabled) {
    document.getElementById('start_' + key).disabled = !enabled;
    document.getElementById('end_'   + key).disabled = !enabled;
}
</script>

</x-layout-dashboard>
