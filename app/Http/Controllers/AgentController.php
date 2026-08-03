<?php

namespace App\Http\Controllers;

use App\Mail\AgentInvitationMail;
use App\Models\Agent;
use App\Models\AgentInvitation;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AgentController extends Controller
{
    public function index(Request $request)
    {
        $agents = Agent::where('user_id', $request->user()->id)
            ->with(['team', 'agentUser', 'invitations' => fn($q) => $q->whereNull('accepted_at')->where('expires_at', '>', now())])
            ->get();
        $teams  = Team::where('user_id', $request->user()->id)->get();

        return view('theme::pages.agents.index', compact('agents', 'teams'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                => 'required|string|max:100',
            'email'               => 'nullable|email|max:150',
            'role'                => 'required|in:agent,supervisor,admin',
            'team_id'             => 'nullable|exists:teams,id',
            'max_concurrent_chats'=> 'required|integer|min:1|max:100',
        ]);

        Agent::create([
            'user_id'             => $request->user()->id,
            'name'                => $request->name,
            'email'               => $request->email,
            'role'                => $request->role,
            'team_id'             => $request->team_id,
            'max_concurrent_chats'=> $request->max_concurrent_chats,
            'status'              => 'offline',
        ]);

        return back()->with('success', __('Agent created.'));
    }

    public function update(Request $request, $id)
    {
        $agent = Agent::where('user_id', $request->user()->id)->findOrFail($id);
        $request->validate([
            'name'                => 'required|string|max:100',
            'email'               => 'nullable|email|max:150',
            'role'                => 'required|in:agent,supervisor,admin',
            'team_id'             => 'nullable|exists:teams,id',
            'status'              => 'required|in:online,offline,busy',
            'max_concurrent_chats'=> 'required|integer|min:1|max:100',
        ]);

        $agent->update($request->only('name','email','role','team_id','status','max_concurrent_chats'));

        return back()->with('success', __('Agent updated.'));
    }

    public function destroy(Request $request, $id)
    {
        Agent::where('user_id', $request->user()->id)->findOrFail($id)->delete();
        return back()->with('success', __('Agent deleted.'));
    }

    // ── Team CRUD ──────────────────────────────────────────────────────────

    public function storeTeam(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:100',
            'routing_rules' => 'nullable|json',
        ]);

        Team::create([
            'user_id'       => $request->user()->id,
            'name'          => $request->name,
            'routing_rules' => $request->routing_rules ? json_decode($request->routing_rules, true) : null,
        ]);

        return back()->with('success', __('Team created.'));
    }

    public function updateTeam(Request $request, $id)
    {
        $team = Team::where('user_id', $request->user()->id)->findOrFail($id);
        $request->validate([
            'name'          => 'required|string|max:100',
            'routing_rules' => 'nullable|json',
        ]);

        $team->update([
            'name'          => $request->name,
            'routing_rules' => $request->routing_rules ? json_decode($request->routing_rules, true) : null,
        ]);

        return back()->with('success', __('Team updated.'));
    }

    public function destroyTeam(Request $request, $id)
    {
        Team::where('user_id', $request->user()->id)->findOrFail($id)->delete();
        return back()->with('success', __('Team deleted.'));
    }

    // ── Invite agent ──────────────────────────────────────────────────────

    public function sendInvite(Request $request, $id)
    {
        $agent = Agent::where('user_id', $request->user()->id)->findOrFail($id);

        if (!$agent->email) {
            return back()->with('error', __('This agent has no email address. Add one before sending an invite.'));
        }

        if ($agent->agentUser()->exists()) {
            return back()->with('error', __('This agent already has an active account.'));
        }

        // Invalidate any old pending invites
        AgentInvitation::where('agent_id', $agent->id)->whereNull('accepted_at')->delete();

        $invitation = AgentInvitation::create([
            'agent_id'   => $agent->id,
            'token'      => Str::random(64),
            'expires_at' => now()->addDays(3),
        ]);

        $acceptUrl = route('agent.invite.show', $invitation->token);

        Mail::to($agent->email)->send(new AgentInvitationMail($invitation, $acceptUrl));

        return back()->with('success', __('Invitation sent to :email.', ['email' => $agent->email]));
    }

    // ── Agent status toggle (AJAX) ─────────────────────────────────────────

    public function setStatus(Request $request, $id)
    {
        $agent = Agent::where('user_id', $request->user()->id)->findOrFail($id);
        $request->validate(['status' => 'required|in:online,offline,busy']);
        $agent->update(['status' => $request->status, 'last_seen_at' => now()]);
        return response()->json(['ok' => true, 'status' => $agent->status]);
    }
}
