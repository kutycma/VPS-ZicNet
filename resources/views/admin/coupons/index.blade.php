@extends('layouts.admin')
@section('title', 'Mã giảm giá')
@section('page_title', 'Quản lý mã giảm giá')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap" style="gap:10px;">
        <h3 class="card-title"><i class="fas fa-ticket-alt mr-1"></i> Danh sách mã</h3>
        <div class="d-flex" style="gap:8px;">
            <form class="form-inline" method="GET">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Tìm mã..." value="{{ request('search') }}">
                <select name="status" class="form-control form-control-sm ml-1">
                    <option value="">Tất cả</option>
                    <option value="active" {{ request('status')==='active'?'selected':'' }}>Active</option>
                    <option value="inactive" {{ request('status')==='inactive'?'selected':'' }}>Inactive</option>
                </select>
                <button class="btn btn-sm btn-outline-primary ml-1"><i class="fas fa-search"></i></button>
            </form>
            <a href="{{ route('admin.coupons.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> Tạo mã</a>
        </div>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Loại</th>
                    <th>Giá trị</th>
                    <th>Sử dụng</th>
                    <th>Giới hạn</th>
                    <th>Hạn SD</th>
                    <th>TT</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($coupons as $coupon)
                <tr>
                    <td>
                        <code style="font-size:0.95rem; font-weight:700; color:#6366f1;">{{ $coupon->code }}</code>
                        @if($coupon->description)
                            <br><small class="text-muted">{{ Str::limit($coupon->description, 40) }}</small>
                        @endif
                    </td>
                    <td><span class="badge badge-{{ $coupon->type==='percent'?'info':'success' }}">{{ $coupon->getTypeLabel() }}</span></td>
                    <td><strong>{{ $coupon->getValueDisplay() }}</strong></td>
                    <td>{{ $coupon->getUsageDisplay() }}</td>
                    <td>
                        <small>{!! $coupon->getRestrictionDisplay() !!}</small>
                    </td>
                    <td>
                        @if($coupon->expires_at)
                            <small class="{{ $coupon->expires_at->isPast() ? 'text-danger' : '' }}">
                                {{ $coupon->expires_at->format('d/m/Y') }}
                            </small>
                        @else
                            <small class="text-muted">Vĩnh viễn</small>
                        @endif
                    </td>
                    <td>
                        <form action="{{ route('admin.coupons.toggle-status', $coupon) }}" method="POST" style="display:inline;">
                            @csrf @method('PATCH')
                            <button type="submit" class="badge badge-{{ $coupon->status==='active'?'success':'danger' }}" style="border:none;cursor:pointer;">
                                {{ $coupon->status }}
                            </button>
                        </form>
                    </td>
                    <td>
                        <a href="{{ route('admin.coupons.edit', $coupon) }}" class="btn btn-sm btn-outline-warning"><i class="fas fa-edit"></i></a>
                        <form action="{{ route('admin.coupons.destroy', $coupon) }}" method="POST" style="display:inline;" onsubmit="return confirm('Xóa mã {{ $coupon->code }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-4 text-muted"><i class="fas fa-ticket-alt mr-1"></i> Chưa có mã giảm giá nào</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($coupons->hasPages())
    <div class="card-footer">{{ $coupons->links() }}</div>
    @endif
</div>
@endsection
