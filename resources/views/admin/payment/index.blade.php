@extends('layouts.admin')
@section('title', 'Thanh toán')
@section('page_title', 'Cấu hình phương thức thanh toán')
@section('content')

{{-- Info --}}
<div class="alert alert-info" style="border-radius:12px; border:none; background:linear-gradient(135deg, #eff6ff, #dbeafe);">
    <i class="fas fa-info-circle mr-2"></i>
    <strong>Hướng dẫn:</strong> Mỗi phương thức thanh toán = 1 ngân hàng/ví. Cấu hình API URL + API Key để hệ thống tự động xác nhận nạp tiền cho phương thức đó.
</div>

{{-- Danh sách payment methods --}}
<div class="row">
    <div class="col-lg-8">
        @forelse($methods as $m)
        <div class="card" style="border-radius:14px; {{ $m->is_active ? '' : 'opacity:0.7;' }}">
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                <h3 class="card-title">
                    <i class="fas fa-university mr-2 text-primary"></i>
                    {{ $m->name }}
                    @if($m->bank_name) <small class="text-muted">- {{ $m->bank_name }}</small> @endif
                </h3>
                <div style="display:flex; align-items:center; gap:8px;">
                    <span class="badge badge-{{ $m->is_active ? 'success' : 'danger' }}">{{ $m->is_active ? 'Bật' : 'Tắt' }}</span>
                    @if($m->hasApiConfig())
                        <span class="badge badge-info"><i class="fas fa-bolt mr-1"></i>API cấu hình</span>
                    @else
                        <span class="badge badge-secondary"><i class="fas fa-times mr-1"></i>Chưa API</span>
                    @endif
                    <button class="btn btn-sm btn-outline-primary" type="button" data-toggle="collapse" data-target="#editMethod{{ $m->id }}" style="border-radius:8px;">
                        <i class="fas fa-edit mr-1"></i> Sửa
                    </button>
                    <form action="{{ route('admin.payment.destroy', $m) }}" method="POST" style="display:inline;" onsubmit="return confirm('Xóa phương thức {{ $m->name }}?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" style="border-radius:8px;"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
            </div>

            {{-- Summary row --}}
            <div class="card-body py-2" style="background:#f8fafc;">
                <div style="display:flex; gap:25px; flex-wrap:wrap; font-size:0.9rem;">
                    @if($m->account_number)
                    <span><i class="fas fa-hashtag mr-1 text-muted"></i><strong>{{ $m->account_number }}</strong></span>
                    @endif
                    @if($m->account_name)
                    <span><i class="fas fa-user mr-1 text-muted"></i>{{ $m->account_name }}</span>
                    @endif
                    @if($m->api_url)
                    <span><i class="fas fa-link mr-1 text-info"></i><code style="font-size:0.8rem;">{{ Str::limit($m->api_url, 50) }}</code></span>
                    @endif
                    <span><i class="fas fa-sort mr-1 text-muted"></i>Thứ tự: {{ $m->sort_order }}</span>
                </div>
            </div>

            {{-- Edit form (collapsed) --}}
            <div class="collapse" id="editMethod{{ $m->id }}">
                <form action="{{ route('admin.payment.update', $m) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="card-body" style="border-top:1px solid #f1f5f9;">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 style="font-weight:700; color:#6366f1; margin-bottom:15px;">
                                    <i class="fas fa-building mr-1"></i> Thông tin ngân hàng
                                </h6>
                                <div class="form-group">
                                    <label>Tên phương thức <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" value="{{ $m->name }}" required style="border-radius:8px;">
                                </div>
                                <div class="form-group">
                                    <label>Ngân hàng</label>
                                    <input type="text" name="bank_name" class="form-control" value="{{ $m->bank_name }}" placeholder="VD: ACB, Vietcombank" style="border-radius:8px;">
                                </div>
                                <div class="form-group">
                                    <label>Số tài khoản</label>
                                    <input type="text" name="account_number" class="form-control" value="{{ $m->account_number }}" style="border-radius:8px;">
                                </div>
                                <div class="form-group">
                                    <label>Chủ tài khoản</label>
                                    <input type="text" name="account_name" class="form-control" value="{{ $m->account_name }}" style="border-radius:8px;">
                                </div>
                                <div class="form-group">
                                    <label>Hướng dẫn (hiển thị cho user)</label>
                                    <textarea name="instructions" class="form-control" rows="2" style="border-radius:8px;" placeholder="Hướng dẫn bổ sung cho người dùng...">{{ $m->instructions }}</textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 style="font-weight:700; color:#10b981; margin-bottom:15px;">
                                    <i class="fas fa-bolt mr-1"></i> Cấu hình API (tự động xác nhận)
                                </h6>
                                <div class="alert alert-light" style="border-radius:10px; font-size:0.85rem; border:1px solid #e2e8f0;">
                                    <i class="fas fa-info-circle text-info mr-1"></i>
                                    Nhập API URL và API Key để hệ thống tự động kiểm tra lịch sử giao dịch và xác nhận nạp tiền cho phương thức này.
                                </div>
                                <div class="form-group">
                                    <label>API URL</label>
                                    <input type="url" name="api_url" class="form-control" value="{{ $m->api_url }}" placeholder="https://api.mua4g.com/api/historyacb/history" style="border-radius:8px;">
                                </div>
                                <div class="form-group">
                                    <label>API Key</label>
                                    <input type="password" name="api_key" class="form-control" value="" placeholder="{{ $m->api_key ? '••••••••• (đã cấu hình, để trống = giữ nguyên)' : 'Nhập API key' }}" style="border-radius:8px;">
                                    @if($m->api_key)
                                    <small class="text-muted"><i class="fas fa-lock mr-1"></i>API key hiện tại đã được lưu. Để trống nếu không muốn thay đổi.</small>
                                    @endif
                                </div>
                                <hr>
                                <div class="form-group">
                                    <label>Thứ tự hiển thị</label>
                                    <input type="number" name="sort_order" class="form-control" value="{{ $m->sort_order }}" min="0" style="border-radius:8px;">
                                </div>
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="active{{ $m->id }}" name="is_active" value="1" {{ $m->is_active ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="active{{ $m->id }}">Kích hoạt phương thức</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer" style="display:flex; gap:8px; justify-content:flex-end;">
                        <button type="button" class="btn btn-secondary" data-toggle="collapse" data-target="#editMethod{{ $m->id }}" style="border-radius:8px;">Hủy</button>
                        <button type="submit" class="btn btn-primary" style="border-radius:8px;">
                            <i class="fas fa-save mr-1"></i> Lưu thay đổi
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @empty
        <div class="card" style="border-radius:14px;">
            <div class="card-body text-center py-4 text-muted">
                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                <h5>Chưa có phương thức thanh toán nào</h5>
                <p>Thêm mới từ form bên phải</p>
            </div>
        </div>
        @endforelse
    </div>

    {{-- Form thêm mới --}}
    <div class="col-lg-4">
        <div class="card" style="border-radius:14px; position:sticky; top:20px;">
            <div class="card-header" style="background:linear-gradient(135deg, #6366f1, #8b5cf6); color:#fff; border-radius:14px 14px 0 0;">
                <h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>Thêm phương thức mới</h3>
            </div>
            <form action="{{ route('admin.payment.store') }}" method="POST">
                <div class="card-body">@csrf
                    <div class="form-group">
                        <label>Tên phương thức <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="VD: ACB - Nguyễn Văn A" style="border-radius:8px;">
                    </div>
                    <div class="form-group">
                        <label>Loại</label>
                        <select name="gateway" class="form-control" style="border-radius:8px;">
                            <option value="bank_transfer">Chuyển khoản ngân hàng</option>
                            <option value="momo">MoMo</option>
                            <option value="vnpay">VNPAY</option>
                            <option value="zalopay">ZaloPay</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Ngân hàng</label>
                        <input type="text" name="bank_name" class="form-control" placeholder="VD: ACB" style="border-radius:8px;">
                    </div>
                    <div class="form-group">
                        <label>Số tài khoản</label>
                        <input type="text" name="account_number" class="form-control" placeholder="VD: 18656771" style="border-radius:8px;">
                    </div>
                    <div class="form-group">
                        <label>Chủ tài khoản</label>
                        <input type="text" name="account_name" class="form-control" placeholder="VD: NGUYEN VAN A" style="border-radius:8px;">
                    </div>
                    <div class="form-group">
                        <label>Hướng dẫn</label>
                        <textarea name="instructions" class="form-control" rows="2" placeholder="Nội dung hiển thị cho user" style="border-radius:8px;"></textarea>
                    </div>
                    <hr>
                    <h6 style="font-weight:600; color:#10b981; font-size:0.9rem;"><i class="fas fa-bolt mr-1"></i> API tự động xác nhận (tùy chọn)</h6>
                    <div class="form-group">
                        <label>API URL</label>
                        <input type="url" name="api_url" class="form-control" placeholder="https://api.mua4g.com/api/historyacb/history" style="border-radius:8px;">
                    </div>
                    <div class="form-group">
                        <label>API Key</label>
                        <input type="text" name="api_key" class="form-control" placeholder="API key từ nhà cung cấp" style="border-radius:8px;">
                    </div>
                    <div class="form-group">
                        <label>Thứ tự</label>
                        <input type="number" name="sort_order" class="form-control" value="0" min="0" style="border-radius:8px;">
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-block" style="border-radius:8px;">
                        <i class="fas fa-plus mr-1"></i> Thêm phương thức
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
