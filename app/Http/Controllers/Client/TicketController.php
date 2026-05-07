<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Support\Facades\Auth;
use App\Services\Telegram\TelegramService;

class TicketController extends Controller
{
    public function index()
    {
        $tickets = Ticket::where('user_id', Auth::id())->orderBy('updated_at', 'desc')->paginate(15);
        return view('client.tickets.index', compact('tickets'));
    }

    public function create()
    {
        return view('client.tickets.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'department' => 'required|in:technical,sales',
            'priority' => 'required|in:low,medium,high',
            'message' => 'required|string'
        ]);

        $ticket = Ticket::create([
            'user_id' => Auth::id(),
            'subject' => $request->subject,
            'department' => $request->department,
            'priority' => $request->priority,
            'status' => 'open'
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'message' => $request->message
        ]);

        try {
            $telegram = new TelegramService();
            $telegram->sendToAdmin("Khách hàng " . Auth::user()->email . " vừa mở Ticket mới #" . $ticket->id . "\nChủ đề: " . $ticket->subject);
        } catch (\Exception $e) {}

        return redirect()->route('client.tickets.show', $ticket)->with('success', 'Đã gửi yêu cầu hỗ trợ thành công. Chúng tôi sẽ phản hồi sớm nhất.');
    }

    public function show(Ticket $ticket)
    {
        if ($ticket->user_id !== Auth::id()) {
            abort(403);
        }

        $ticket->load('messages.user');
        return view('client.tickets.show', compact('ticket'));
    }

    public function reply(Request $request, Ticket $ticket)
    {
        if ($ticket->user_id !== Auth::id()) {
            abort(403);
        }

        if ($ticket->status === 'closed') {
            return back()->with('error', 'Ticket này đã đóng, không thể trả lời.');
        }

        $request->validate([
            'message' => 'required|string'
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'message' => $request->message
        ]);

        $ticket->update(['status' => 'client-reply', 'updated_at' => now()]);

        try {
            $telegram = new TelegramService();
            $telegram->sendToAdmin("Khách hàng " . Auth::user()->email . " vừa gửi tin nhắn mới vào Ticket #" . $ticket->id);
        } catch (\Exception $e) {}

        return back()->with('success', 'Đã gửi phản hồi thành công.');
    }
}
