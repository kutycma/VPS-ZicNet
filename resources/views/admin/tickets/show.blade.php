@extends('layouts.admin')
@section('title', 'Xử lý Ticket #' . $ticket->id)
@section('page_title', 'Hỗ trợ: ' . $ticket->subject)
@section('content')
<div class="row">
    <div class="col-md-3">
        <div class="card card-primary card-outline">
            <div class="card-body box-profile">
                <h3 class="profile-username text-center">Ticket #{{ $ticket->id }}</h3>
                <p class="text-muted text-center"><span class="badge badge-{{ $ticket->status_color }}">{{ $ticket->status_label }}</span></p>
                <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item">
                        <b>Khách hàng</b> <a class="float-right" href="{{ route('admin.users.edit', $ticket->user_id) }}">{{ $ticket->user->name ?? 'Deleted' }}</a>
                    </li>
                    <li class="list-group-item">
                        <b>Phòng ban</b> <a class="float-right">{{ $ticket->department === 'technical' ? 'Kỹ thuật' : 'Kinh doanh' }}</a>
                    </li>
                    <li class="list-group-item">
                        <b>Mức độ</b> <a class="float-right">{{ strtoupper($ticket->priority) }}</a>
                    </li>
                </ul>
                
                @if($ticket->status !== 'closed')
                @if($ticket->status !== 'resolved')
                <form action="{{ route('admin.tickets.resolve', $ticket) }}" method="POST" class="mb-2" onsubmit="return confirm('Đánh dấu là đã hoàn thành? Ticket sẽ tự đóng sau 24h.');">
                    @csrf
                    <button type="submit" class="btn btn-success btn-block"><b><i class="fas fa-check-circle"></i> Đánh dấu Hoàn thành</b></button>
                </form>
                @endif
                <form action="{{ route('admin.tickets.close', $ticket) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn đóng hồ sơ hỗ trợ này?');">
                    @csrf
                    <button type="submit" class="btn btn-warning btn-block"><b><i class="fas fa-lock"></i> Đóng Ticket Ngay</b></button>
                </form>
                @endif
                <a href="{{ route('admin.tickets.index') }}" class="btn btn-default btn-block mt-2"><b><i class="fas fa-arrow-left"></i> Quay lại</b></a>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <div class="card direct-chat direct-chat-danger">
            <div class="card-header">
                <h3 class="card-title">Tin nhắn hỗ trợ</h3>
            </div>
            
            <div class="card-body">
                <div class="direct-chat-messages" style="height: 600px;">
                    @foreach($ticket->messages as $msg)
                        @php
                            $isAdmin = $msg->user->is_admin ?? false;
                        @endphp
                        @if($isAdmin)
                            <!-- Message to the right (Admin) -->
                            <div class="direct-chat-msg right">
                                <div class="direct-chat-infos clearfix">
                                    <span class="direct-chat-name float-right">Ban Quản Trị ({{ $msg->user->name }})</span>
                                    <span class="direct-chat-timestamp float-left">{{ $msg->created_at->format('d/m/Y H:i:s') }}</span>
                                </div>
                                <img class="direct-chat-img" src="https://ui-avatars.com/api/?name=Admin&background=dc3545&color=fff" alt="Admin Image">
                                <div class="direct-chat-text bg-danger border-danger">
                                    {!! nl2br(e($msg->message)) !!}
                                </div>
                            </div>
                        @else
                            <!-- Message to the left (Client) -->
                            <div class="direct-chat-msg">
                                <div class="direct-chat-infos clearfix">
                                    <span class="direct-chat-name float-left">{{ $msg->user->name ?? 'Guest' }}</span>
                                    <span class="direct-chat-timestamp float-right">{{ $msg->created_at->format('d/m/Y H:i:s') }}</span>
                                </div>
                                <img class="direct-chat-img" src="https://ui-avatars.com/api/?name={{ urlencode($msg->user->name ?? 'G') }}&background=0D8ABC&color=fff" alt="User Image">
                                <div class="direct-chat-text">
                                    {!! nl2br(e($msg->message)) !!}
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
            
            @if($ticket->status !== 'closed')
            <div class="card-footer">
                <form action="{{ route('admin.tickets.reply', $ticket) }}" method="POST">
                    @csrf
                    <div class="input-group mb-2">
                        <textarea name="message" class="form-control" rows="4" placeholder="Nhập câu trả lời cho khách hàng (Sẽ chuyển trạng thái sang Đã trả lời)..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger float-right"><i class="fas fa-reply"></i> Gửi Phản Hồi (Admin)</button>
                </form>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
