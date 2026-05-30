<?php
/*
Copyright © Magd Almuntaser, OneXGen Technology. All rights reserved.
Project: MPWA Whatsapp Gateway | Multi Device
Licensed under the CC BY-NC-ND 4.0 License.
For details, visit https://creativecommons.org/licenses/by-nc-nd/4.0/.
*/

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Coderflex\LaravelTicket\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    /**
     * List every ticket in the system.
     */
    public function index()
    {
        $tickets = Ticket::with('user')->latest()->paginate(15);

        return view('theme::pages.admin.tickets.index', compact('tickets'));
    }

    /**
     * Show a single ticket with its conversation.
     */
    public function show(Ticket $ticket)
    {
        $ticket->load('messages.user', 'user');

        return view('theme::pages.admin.tickets.show', compact('ticket'));
    }

    /**
     * Post an admin reply on an open ticket.
     */
    public function reply(Request $request, Ticket $ticket)
    {
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
     * Close a ticket.
     */
    public function close(Ticket $ticket)
    {
        $ticket->close();

        return back()->with('alert', ['type' => 'success', 'msg' => __('Ticket closed')]);
    }

    /**
     * Reopen a closed ticket.
     */
    public function reopen(Ticket $ticket)
    {
        $ticket->reopen();

        return back()->with('alert', ['type' => 'success', 'msg' => __('Ticket reopened')]);
    }
}
