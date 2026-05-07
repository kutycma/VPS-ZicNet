@extends('layouts.admin')
@section('title', 'Cài đặt')
@section('page_title', 'Cài đặt hệ thống')
@section('content')
<div class="card"><div class="card-header"><h3 class="card-title">Cài đặt chung</h3></div>
<form action="{{ route('admin.settings.update') }}" method="POST"><div class="card-body">@csrf
    <input type="hidden" name="group" value="general">
    <div class="form-group"><label>Tên website</label><input type="text" name="site_name" class="form-control" value="{{ \App\Models\Setting::get('site_name', 'VPS ZicNet') }}"></div>
    <div class="form-group"><label>Mô tả</label><input type="text" name="site_description" class="form-control" value="{{ \App\Models\Setting::get('site_description') }}"></div>
    <div class="form-group"><label>Email liên hệ</label><input type="email" name="contact_email" class="form-control" value="{{ \App\Models\Setting::get('contact_email') }}"></div>
    <div class="form-group"><label>Số điện thoại</label><input type="text" name="contact_phone" class="form-control" value="{{ \App\Models\Setting::get('contact_phone') }}"></div>
    <div class="form-group"><label>Link Telegram Cá Nhân (Dùng cho bong bóng chát)</label><input type="text" name="contact_telegram_url" class="form-control" placeholder="VD: https://t.me/username" value="{{ \App\Models\Setting::get('contact_telegram_url') }}"></div>
</div><div class="card-footer"><button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Lưu cài đặt</button></div></form>
</div>

<div class="card mt-4"><div class="card-header"><h3 class="card-title">Cấu hình Email (SMTP)</h3></div>
<form action="{{ route('admin.settings.update') }}" method="POST"><div class="card-body">@csrf
    <input type="hidden" name="group" value="smtp">
    <div class="form-group"><label>SMTP Host</label><input type="text" name="mail_host" class="form-control" value="{{ \App\Models\Setting::get('mail_host', 'smtp.gmail.com') }}" placeholder="VD: smtp.gmail.com"></div>
    <div class="form-group"><label>SMTP Port</label><input type="text" name="mail_port" class="form-control" value="{{ \App\Models\Setting::get('mail_port', '465') }}" placeholder="VD: 587 hoặc 465"></div>
    <div class="form-group"><label>SMTP Username (Email)</label><input type="text" name="mail_username" class="form-control" value="{{ \App\Models\Setting::get('mail_username') }}"></div>
    <div class="form-group"><label>SMTP Password</label><input type="password" name="mail_password" class="form-control" value="{{ \App\Models\Setting::get('mail_password') }}" placeholder="Mật khẩu ứng dụng (App Password)"></div>
    <div class="form-group"><label>Mã hoá (Encryption)</label>
        <select name="mail_encryption" class="form-control">
            <option value="tls" {{ \App\Models\Setting::get('mail_encryption', 'tls') == 'tls' ? 'selected' : '' }}>TLS</option>
            <option value="ssl" {{ \App\Models\Setting::get('mail_encryption') == 'ssl' ? 'selected' : '' }}>SSL</option>
        </select>
    </div>
    <div class="form-group"><label>Tên người gửi (From Name)</label><input type="text" name="mail_from_name" class="form-control" value="{{ \App\Models\Setting::get('mail_from_name', 'VPS ZicNet') }}"></div>
</div><div class="card-footer"><button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Lưu cấu hình SMTP</button></div></form>
</div>

<div class="card mt-4"><div class="card-header"><h3 class="card-title">Cấu hình Thông báo Toàn hệ thống (Dashboard)</h3></div>
<form action="{{ route('admin.settings.update') }}" method="POST"><div class="card-body">@csrf
    <input type="hidden" name="group" value="notifications">
    
    <div class="row">
        <div class="col-md-6 border-right">
            <h5>Ghim trên đỉnh Dashboard</h5>
            <div class="form-group">
                <label>Bật/Tắt Ghim</label>
                <select name="notice_pinned_active" class="form-control">
                    <option value="1" {{ \App\Models\Setting::get('notice_pinned_active') == '1' ? 'selected' : '' }}>Bật</option>
                    <option value="0" {{ \App\Models\Setting::get('notice_pinned_active', '0') == '0' ? 'selected' : '' }}>Tắt</option>
                </select>
            </div>
            <div class="form-group">
                <label>Nội dung Ghim (Hỗ trợ HTML)</label>
                <textarea name="notice_pinned_text" class="form-control" rows="4" placeholder="VD: <strong>Khuyến mãi 50%</strong> nạp tiền...">{{ \App\Models\Setting::get('notice_pinned_text') }}</textarea>
            </div>
        </div>
        <div class="col-md-6">
            <h5>Popup hiển thị màn hình</h5>
            <div class="form-group">
                <label>Bật/Tắt Popup</label>
                <select name="notice_popup_active" class="form-control">
                    <option value="1" {{ \App\Models\Setting::get('notice_popup_active') == '1' ? 'selected' : '' }}>Bật</option>
                    <option value="0" {{ \App\Models\Setting::get('notice_popup_active', '0') == '0' ? 'selected' : '' }}>Tắt</option>
                </select>
            </div>
            <div class="form-group">
                <label>Nội dung Popup (Hỗ trợ HTML)</label>
                <textarea name="notice_popup_text" class="form-control" rows="4" placeholder="VD: Tham gia nhóm Telegram để nhận mã giảm giá...">{{ \App\Models\Setting::get('notice_popup_text') }}</textarea>
            </div>
        </div>
    </div>
</div><div class="card-footer"><button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Lưu cấu hình Thông báo</button></div></form>
</div>

<div class="card mt-4"><div class="card-header"><h3 class="card-title">Cấu hình nạp tiền</h3></div>
<form action="{{ route('admin.settings.update') }}" method="POST"><div class="card-body">@csrf
    <input type="hidden" name="group" value="bank_api">
    <div class="alert alert-info" style="border-radius:10px;"><i class="fas fa-info-circle mr-1"></i> API URL và API Key của từng ngân hàng được cấu hình riêng trong mục <a href="{{ route('admin.payment.index') }}"><strong>Thanh toán</strong></a>.</div>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group"><label>Prefix mã giao dịch</label><input type="text" name="deposit_prefix" class="form-control" value="{{ \App\Models\Setting::get('deposit_prefix', 'ZICNET') }}" placeholder="ZICNET"></div>
        </div>
        <div class="col-md-6">
            <div class="form-group"><label>Thời gian hết hạn deposit (phút)</label><input type="number" name="deposit_expiry_minutes" class="form-control" value="{{ \App\Models\Setting::get('deposit_expiry_minutes', 30) }}" min="5" max="120"></div>
        </div>
    </div>
</div><div class="card-footer"><button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Lưu cấu hình nạp tiền</button></div></form>
</div>


<div class="card mt-4"><div class="card-header"><h3 class="card-title">Cấu hình Telegram Bot</h3></div>
<form action="{{ route('admin.settings.update') }}" method="POST"><div class="card-body">@csrf
    <input type="hidden" name="group" value="telegram">
    <div class="form-group"><label>Telegram Bot Token</label><input type="password" name="telegram_bot_token" class="form-control" value="{{ \App\Models\Setting::get('telegram_bot_token') }}" placeholder="123456789:ABCdefGHIjklMNOpqrSTUvwxYZ"></div>
    <div class="form-group"><label>Admin Chat ID (Nhận thông báo đơn hàng/ticket mới)</label><input type="text" name="telegram_admin_id" class="form-control" value="{{ \App\Models\Setting::get('telegram_admin_id') }}" placeholder="Ví dụ: 12345678"></div>
    <div class="form-group"><label>Ngưỡng cảnh báo số dư NCC (VNĐ)</label><input type="number" name="provider_balance_threshold" class="form-control" value="{{ \App\Models\Setting::get('provider_balance_threshold', 200000) }}"></div>
    <div class="alert alert-info mt-3"><i class="fas fa-info-circle"></i> Đăng ký Webhook cho Bot tự động tại route: <code>{{ route('webhook.telegram') ?? url('webhook/telegram') }}</code>. Nhờ người làm mã chạy lệnh curl hoặc gán webhook thủ công nhé.</div>
</div><div class="card-footer"><button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Lưu cấu hình Telegram</button></div></form>
</div>
@endsection
