<div class="mb-3">
    <label class="form-label fw-semibold small">{{ __('Team Name') }} <span class="text-danger">*</span></label>
    <input name="name" class="form-control form-control-sm" value="{{ old('name', $team?->name) }}" required>
</div>
<div class="mb-2">
    <label class="form-label fw-semibold small">{{ __('Routing Rules (JSON)') }}</label>
    <textarea name="routing_rules" class="form-control form-control-sm" rows="4"
        placeholder='[{"field":"keyword","value":"billing"}]'>{{ old('routing_rules', $team ? json_encode($team->routing_rules) : '') }}</textarea>
    <div class="form-text">
        {{ __('Example: [{"field":"keyword","value":"billing"}] — routes conversations containing "billing" to this team.') }}
    </div>
</div>
