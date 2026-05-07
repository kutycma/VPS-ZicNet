<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Support\Facades\Auth;
use App\Services\Telegram\TelegramService;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $query = Ticket::with('user');

        if ($request->has('department') && $request->department) {
            $query->where('department', $request->department);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Sort: Open and Client-reply first
        $tickets = $query->orderByRaw(
            "FIELD(status, 'client-reply', 'open', 'answered', 'resolved', 'closed')"
        )->orderBy('updated_at', 'desc')->paginate(20);

        return view('admin.tickets.index', compact('tickets'));
    }

    public function show(Ticket $ticket)
    {
        $ticket->load('messages.user');
        
        // Auto mark as open -> answered conceptually if you want, but better leave it on reply
        return view('admin.tickets.show', compact('ticket'));
    }

    public function reply(Request $request, Ticket $ticket)
    {
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

        $ticket->update(['status' => 'answered', 'updated_at' => now()]);

        try {
            if ($ticket->user) {
                $telegram = new TelegramService();
                $telegram->sendToUser($ticket->user, "Quản trị viên vừa trả lời Ticket #" . $ticket->id . " của bạn.\nVui lòng đăng nhập hệ thống để xem chi tiết.");
            }
        } catch (\Exception $e) {}

        return back()->with('success', 'Đã phản hồi cho khách hàng.');
    }

    public function close(Ticket $ticket)
    {
        $ticket->update(['status' => 'closed', 'updated_at' => now()]);
        return back()->with('success', 'Đã đóng ticket thành công.');
    }

    public function resolve(Ticket $ticket)
    {
        $ticket->update(['status' => 'resolved', 'updated_at' => now()]);
        
        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'message' => "Hệ thống: Quản trị viên đã đánh dấu yêu cầu này là **Hoàn Thành**. Nếu Quý khách không có phản hồi nào khác, hệ thống sẽ tự động khép lại hồ sơ hỗ trợ sau 24 giờ."
        ]);

        try {
            if ($ticket->user) {
                $telegram = new TelegramService();
                $telegram->sendToUser($ticket->user, "Ticket #" . $ticket->id . " của bạn đã được đánh dấu HOÀN THÀNH. Cảm ơn bạn đã sử dụng dịch vụ!");
            }
        } catch (\Exception $e) {}

        return back()->with('success', 'Đã đánh dấu hoàn thành ticket.');
    }
}
