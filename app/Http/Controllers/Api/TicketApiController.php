<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketApiController extends Controller
{
    /**
     * GET /api/tickets
     */
    public function index()
    {
        $tickets = Auth::user()->tickets()->latest()->paginate(15);

        return response()->json([
            'data' => array_map(function ($t) { return $this->formatTicket($t); }, $tickets->items()),
            'pagination' => [
                'current_page' => $tickets->currentPage(),
                'last_page'    => $tickets->lastPage(),
                'total'        => $tickets->total(),
            ],
        ]);
    }

    /**
     * POST /api/tickets
     */
    public function store(Request $request)
    {
        $request->validate([
            'subject'  => 'required|string|max:255',
            'message'  => 'required|string',
            'priority' => 'nullable|in:low,medium,high',
        ]);

        $ticket = Ticket::create([
            'user_id'  => Auth::id(),
            'subject'  => $request->subject,
            'priority' => $request->priority ?? 'medium',
            'status'   => 'open',
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id'   => Auth::id(),
            'message'   => $request->message,
            'is_staff'  => false,
        ]);

        return response()->json(['success' => true, 'ticket' => $this->formatTicket($ticket)], 201);
    }

    /**
     * GET /api/tickets/{ticket}
     */
    public function show(Ticket $ticket)
    {
        if ($ticket->user_id !== Auth::id()) abort(403);

        $ticket->load('messages.user');

        return response()->json([
            'ticket'   => $this->formatTicket($ticket),
            'messages' => $ticket->messages->map(function ($m) {
                return [
                    'id'         => $m->id,
                    'message'    => $m->message,
                    'is_staff'   => (bool) $m->is_staff,
                    'user'       => $m->user ? ['id' => $m->user->id, 'name' => $m->user->name] : null,
                    'created_at' => $m->created_at ? $m->created_at->toISOString() : null,
                ];
            })->values()->all(),
        ]);
    }

    /**
     * POST /api/tickets/{ticket}/reply
     */
    public function reply(Request $request, Ticket $ticket)
    {
        if ($ticket->user_id !== Auth::id()) abort(403);
        $request->validate(['message' => 'required|string']);

        if (in_array($ticket->status, ['closed', 'resolved'])) {
            return response()->json(['message' => 'This ticket is closed.'], 422);
        }

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id'   => Auth::id(),
            'message'   => $request->message,
            'is_staff'  => false,
        ]);

        $ticket->update(['status' => 'open']);

        return response()->json(['success' => true]);
    }

    private function formatTicket(Ticket $ticket): array
    {
        return [
            'id'         => $ticket->id,
            'subject'    => $ticket->subject,
            'status'     => $ticket->status,
            'priority'   => $ticket->priority,
            'created_at' => $ticket->created_at ? $ticket->created_at->toISOString() : null,
            'updated_at' => $ticket->updated_at ? $ticket->updated_at->toISOString() : null,
        ];
    }
}
