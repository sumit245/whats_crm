<?php
/*
Copyright © Magd Almuntaser, OneXGen Technology. All rights reserved.
Project: MPWA Whatsapp Gateway | Multi Device
Licensed under the CC BY-NC-ND 4.0 License.
For details, visit https://creativecommons.org/licenses/by-nc-nd/4.0/.
*/

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Coderflex\LaravelTicket\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    /**
     * List the authenticated user's tickets.
     */
    public function index()
    {
        $tickets = Auth::user()->tickets()->latest()->paginate(10);

        return view('theme::pages.user.tickets.index', compact('tickets'));
    }

    /**
     * Show the create-ticket form.
     */
    public function create()
    {
        return view('theme::pages.user.tickets.create');
    }

    /**
     * Store a new ticket and its first message.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'priority' => 'required|in:low,medium,high',
            'message' => 'required|string',
        ]);

        $ticket = Auth::user()->tickets()->create([
            'uuid' => (string) Str::uuid(),
            'title' => $request->input('title'),
            'priority' => $request->input('priority'),
            'message' => $request->input('message'),
            'status' => 'open',
        ]);

        $ticket->messages()->create([
            'user_id' => Auth::id(),
            'message' => $request->input('message'),
        ]);

        return redirect()
            ->route('user.tickets.index')
            ->with('alert', ['type' => 'success', 'msg' => __('Ticket created successfully')]);
    }

    /**
     * Show a single ticket owned by the authenticated user.
     */
    public function show(Ticket $ticket)
    {
        $this->authorizeOwner($ticket);

        $ticket->load('messages.user');

        return view('theme::pages.user.tickets.show', compact('ticket'));
    }

    /**
     * Add a reply to an open ticket.
     */
    public function reply(Request $request, Ticket $ticket)
    {
        $this->authorizeOwner($ticket);

        $request->validate([
            'message' => 'required|string',
        ]);

        if ($ticket->status !== 'open') {
            return back()->with('alert', ['type' => 'danger', 'msg' => __('This ticket is closed')]);
        }

        $ticket->messages()->create([
            'user_id' => Auth::id(),
            'message' => $request->input('message'),
        ]);

        return back()->with('alert', ['type' => 'success', 'msg' => __('Reply sent')]);
    }

    /**
     * Ensure the ticket belongs to the authenticated user.
     */
    protected function authorizeOwner(Ticket $ticket): void
    {
        abort_unless((int) $ticket->user_id === (int) Auth::id(), 403);
    }
}
