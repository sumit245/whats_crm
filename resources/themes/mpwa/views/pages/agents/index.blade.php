<x-layout-dashboard title="{{ __('Agents & Teams') }}">

<div class="app-content py-3 px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-0 fw-semibold">{{ __('Agents & Teams') }}</h5>
            <small class="text-muted">{{ __('Manage your support agents, teams, and routing rules.') }}</small>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
            {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">

        {{-- ── TEAMS ──────────────────────────────────────────────────────── --}}
        <div class="col-md-5">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold small">{{ __('Teams') }}</span>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newTeamModal">
                        <i class="bi bi-plus"></i> {{ __('New Team') }}
                    </button>
                </div>
                <div class="card-body p-0">
                    @forelse($teams as $team)
                    <div class="d-flex align-items-center px-3 py-2 border-bottom">
                        <div class="flex-grow-1">
                            <div class="fw-semibold small">{{ $team->name }}</div>
                            <div class="text-muted" style="font-size:11px">
                                {{ $team->agents->count() ?? 0 }} {{ __('agents') }}
                                @if($team->routing_rules)
                                    · {{ count($team->routing_rules) }} {{ __('rules') }}
                                @endif
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-xs btn-outline-secondary"
                                onclick="editTeam({{ $team->id }}, '{{ addslashes($team->name) }}', '{{ addslashes(json_encode($team->routing_rules)) }}')">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form action="{{ route('teams.destroy', $team->id) }}" method="POST"
                                onsubmit="return confirm('{{ __('Delete this team?') }}')">
                                @csrf @method('DELETE')
                                <button class="btn btn-xs btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-muted p-4 small">{{ __('No teams yet.') }}</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ── AGENTS ─────────────────────────────────────────────────────── --}}
        <div class="col-md-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold small">{{ __('Agents') }}</span>
                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#newAgentModal">
                        <i class="bi bi-person-plus"></i> {{ __('Add Agent') }}
                    </button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Role') }}</th>
                                <th>{{ __('Team') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Chats') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($agents as $agent)
                            <tr>
                                <td>
                                    <div class="fw-semibold small">{{ $agent->name }}</div>
                                    <div class="text-muted" style="font-size:11px">{{ $agent->email }}</div>
                                </td>
                                <td>
                                    @php
                                        $roleClass = ['agent'=>'secondary','supervisor'=>'warning','admin'=>'danger'][$agent->role] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $roleClass }}-subtle text-{{ $roleClass }} small">{{ ucfirst($agent->role) }}</span>
                                </td>
                                <td><small>{{ $agent->team?->name ?? '—' }}</small></td>
                                <td>
                                    @php $statusClass = ['online'=>'success','busy'=>'warning','offline'=>'secondary'][$agent->status]; @endphp
                                    <span class="badge bg-{{ $statusClass }}-subtle text-{{ $statusClass }} small" id="status-badge-{{ $agent->id }}">
                                        {{ ucfirst($agent->status) }}
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">{{ $agent->active_chat_count }}/{{ $agent->max_concurrent_chats }}</small>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <button class="btn btn-xs btn-outline-secondary"
                                            onclick="editAgent({{ $agent->id }}, '{{ addslashes($agent->name) }}', '{{ $agent->email }}', '{{ $agent->role }}', {{ $agent->team_id ?? 'null' }}, '{{ $agent->status }}', {{ $agent->max_concurrent_chats }})">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="{{ route('agents.destroy', $agent->id) }}" method="POST"
                                            onsubmit="return confirm('{{ __('Delete this agent?') }}')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-xs btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted py-4 small">{{ __('No agents yet. Add your first agent above.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- New Agent Modal --}}
<div class="modal fade" id="newAgentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">{{ __('Add Agent') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('agents.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    @include('theme::pages.agents._form', ['agent' => null])
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-sm btn-success">{{ __('Save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Agent Modal --}}
<div class="modal fade" id="editAgentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">{{ __('Edit Agent') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editAgentForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-body" id="editAgentBody"></div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-sm btn-primary">{{ __('Update') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- New Team Modal --}}
<div class="modal fade" id="newTeamModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">{{ __('New Team') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('teams.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    @include('theme::pages.agents._team_form', ['team' => null])
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-sm btn-primary">{{ __('Save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Team Modal --}}
<div class="modal fade" id="editTeamModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">{{ __('Edit Team') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editTeamForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-body" id="editTeamBody"></div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-sm btn-primary">{{ __('Update') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const TEAMS_DATA = @json($teams->map(fn($t) => ['id'=>$t->id,'name'=>$t->name]));

function editAgent(id, name, email, role, teamId, status, maxChats) {
    document.getElementById('editAgentForm').action = '/agents/' + id;
    document.getElementById('editAgentBody').innerHTML = agentFormHtml(name, email, role, teamId, status, maxChats);
    new bootstrap.Modal(document.getElementById('editAgentModal')).show();
}

function agentFormHtml(name, email, role, teamId, status, maxChats) {
    const roles   = ['agent','supervisor','admin'];
    const statuses= ['online','offline','busy'];
    const teamOpts = TEAMS_DATA.map(t =>
        `<option value="${t.id}" ${teamId == t.id ? 'selected' : ''}>${t.name}</option>`
    ).join('');

    return `
    <div class="mb-3"><label class="form-label fw-semibold small">{{ __('Name') }} *</label>
        <input name="name" class="form-control form-control-sm" value="${name}" required></div>
    <div class="mb-3"><label class="form-label fw-semibold small">{{ __('Email') }}</label>
        <input name="email" type="email" class="form-control form-control-sm" value="${email || ''}"></div>
    <div class="row g-2">
        <div class="col-6 mb-3"><label class="form-label fw-semibold small">{{ __('Role') }} *</label>
            <select name="role" class="form-select form-select-sm" required>
                ${roles.map(r => `<option ${role===r?'selected':''}>${r}</option>`).join('')}
            </select></div>
        <div class="col-6 mb-3"><label class="form-label fw-semibold small">{{ __('Status') }}</label>
            <select name="status" class="form-select form-select-sm">
                ${statuses.map(s => `<option ${status===s?'selected':''}>${s}</option>`).join('')}
            </select></div>
    </div>
    <div class="row g-2">
        <div class="col-6 mb-3"><label class="form-label fw-semibold small">{{ __('Team') }}</label>
            <select name="team_id" class="form-select form-select-sm">
                <option value="">{{ __('No team') }}</option>${teamOpts}
            </select></div>
        <div class="col-6 mb-3"><label class="form-label fw-semibold small">{{ __('Max Chats') }}</label>
            <input name="max_concurrent_chats" type="number" min="1" max="100" class="form-control form-control-sm" value="${maxChats}"></div>
    </div>`;
}

function editTeam(id, name, rules) {
    document.getElementById('editTeamForm').action = '/teams/' + id;
    document.getElementById('editTeamBody').innerHTML = teamFormHtml(name, rules);
    new bootstrap.Modal(document.getElementById('editTeamModal')).show();
}

function teamFormHtml(name, rules) {
    return `
    <div class="mb-3"><label class="form-label fw-semibold small">{{ __('Team Name') }} *</label>
        <input name="name" class="form-control form-control-sm" value="${name}" required></div>
    <div class="mb-2"><label class="form-label fw-semibold small">{{ __('Routing Rules (JSON)') }}</label>
        <textarea name="routing_rules" class="form-control form-control-sm" rows="4"
            placeholder='[{"field":"keyword","value":"billing"}]'>${rules !== 'null' ? rules : ''}</textarea>
        <div class="form-text">{{ __('Each rule routes conversations matching the keyword to this team.') }}</div>
    </div>`;
}
</script>

</x-layout-dashboard>
