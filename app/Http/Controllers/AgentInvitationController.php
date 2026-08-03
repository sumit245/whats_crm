<?php

namespace App\Http\Controllers;

use App\Models\AgentInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AgentInvitationController extends Controller
{
    public function show(string $token)
    {
        $invitation = AgentInvitation::with('agent.team')
            ->where('token', $token)
            ->firstOrFail();

        if ($invitation->isExpired()) {
            return view('theme::auth.agent-accept-invite-expired');
        }

        if ($invitation->isAccepted()) {
            return redirect()->route('login')->with('info', __('This invitation has already been used. Please log in.'));
        }

        return view('theme::auth.agent-accept-invite', compact('invitation'));
    }

    public function accept(Request $request, string $token)
    {
        $invitation = AgentInvitation::with('agent')
            ->where('token', $token)
            ->firstOrFail();

        if ($invitation->isExpired()) {
            return back()->with('error', __('This invitation link has expired.'));
        }

        if ($invitation->isAccepted()) {
            return redirect()->route('login')->with('info', __('Already activated. Please log in.'));
        }

        $request->validate([
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $agent = $invitation->agent;

        // Create the linked User account
        $user = User::create([
            'agent_id' => $agent->id,
            'name'     => $agent->name,
            'username' => $request->username,
            'email'    => $agent->email,
            'password' => Hash::make($request->password),
        ]);

        // Mark invitation as accepted
        $invitation->update(['accepted_at' => now()]);

        // Log them in immediately
        Auth::login($user);

        return redirect()->route('chat.index')
            ->with('success', __('Welcome! Your account is ready. You can now handle your assigned conversations.'));
    }
}
