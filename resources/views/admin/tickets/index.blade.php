@extends('layouts.admin')
@section('title', 'Quản lý Hỗ trợ (Tickets)')
@section('page_title', 'Tất cả Yêu cầu Hỗ trợ')
@section('content')
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">Bộ lọc Tickets</h3>
        <div class="card-tools">
            <form action="{{ route('admin.tickets.index') }}" method="GET" class="form-inline">
                <select name="department" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                    <option value="">-- Tất cả phòng ban --</option>
                    <option value="technical" {{ request('department') == 'technical' ? 'selected' : '' }}>Kỹ thuật</option>
                    <option value="sales" {{ request('department') == 'sales' ? 'selected' : '' }}>Kinh doanh</option>
                </select>
                <select name="status" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                    <option value="">-- Tất cả trạng thái --</option>
                    <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Chờ xử lý</option>
                    <option value="client-reply" {{ request('status') == 'client-reply' ? 'selected' : '' }}>Khách phản hồi</option>
                    <option value="answered" {{ request('status') == 'answered' ? 'selected' : '' }}>Đã trả lời</option>
                    <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Hoàn thành</option>
                    <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Đã đóng</option>
                </select>
                <a href="{{ route('admin.tickets.index') }}" class="btn btn-sm btn-default"><i class="fas fa-sync"></i> Xoá lọc</a>
            </form>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Chủ đề</th>
                    <th>Khách hàng</th>
                    <th>Phòng ban</th>
                    <th>Mức độ</th>
                    <th>Trạng thái</th>
                    <th>Cập nhật cuối</th>
                    <th class="text-right">Hành động</th>
                </tr>
            </thead>
            <tbody>
            @forelse($tickets as $ticket)
                <tr>
                    <td>#{{ $ticket->id }}</td>
                    <td><strong>{{ $ticket->subject }}</strong></td>
                    <td>
                        {{ $ticket->user->name ?? 'User Deleted' }}<br>
                        <small class="text-muted">{{ $ticket->user->email ?? '' }}</small>
                    </td>
                    <td>{{ $ticket->department === 'technical' ? 'Kỹ thuật' : 'Kinh doanh' }}</td>
                    <td>
                        @if($ticket->priority === 'high') <span class="badge badge-danger">Gấp</span>
                        @elseif($ticket->priority === 'medium') <span class="badge badge-warning">Vừa</span>
                        @else <span class="badge badge-secondary">Thấp</span> @endif
                    </td>
                    <td><span class="badge badge-{{ $ticket->status_color }}">{{ $ticket->status_label }}</span></td>
                    <td>{{ $ticket->updated_at->diffForHumans() }}</td>
                    <td class="text-right">
                        <a href="{{ route('admin.tickets.show', $ticket) }}" class="btn btn-sm btn-primary">Xử lý <i class="fas fa-arrow-right"></i></a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                        <i class="fas fa-check-circle fa-2x mb-2 d-block text-success"></i>
                        Không có ticket nào cần xử lý với bộ lọc này.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
        <div class="px-3 pb-3 mt-3">{{ $tickets->links() }}</div>
    </div>
</div>
@endsection
