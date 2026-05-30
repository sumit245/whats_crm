<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Team;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function index(Request $request)
    {
        $agents = Agent::where('user_id', $request->user()->id)
            ->with('team')
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

    // ── Agent status toggle (AJAX) ─────────────────────────────────────────

    public function setStatus(Request $request, $id)
    {
        $agent = Agent::where('user_id', $request->user()->id)->findOrFail($id);
        $request->validate(['status' => 'required|in:online,offline,busy']);
        $agent->update(['status' => $request->status, 'last_seen_at' => now()]);
        return response()->json(['ok' => true, 'status' => $agent->status]);
    }
}
