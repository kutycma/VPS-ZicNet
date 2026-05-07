<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Arial', sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #f1f5f9; padding-bottom: 20px; }
        .logo { font-size: 24px; font-weight: bold; color: #6366f1; }
        .content { font-size: 16px; line-height: 1.6; }
        .details-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .details-table th, .details-table td { padding: 12px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        .details-table th { background-color: #f8fafc; color: #64748b; font-weight: 600; width: 40%; }
        .btn { display: inline-block; padding: 12px 24px; background-color: #6366f1; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 25px; }
        .footer { text-align: center; margin-top: 30px; font-size: 13px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">VPS ZicNet</div>
            <h2 style="color: #10b981;">Đơn hàng xử lý thành công!</h2>
        </div>
        <div class="content">
            <p>Chào <strong>{{ $order->user->name }}</strong>,</p>
            <p>Chúng tôi xin thông báo đơn hàng <strong>#{{ $order->id }}</strong> của bạn đã được hệ thống xử lý hoàn tất. Cảm ơn bạn đã tin tưởng sử dụng dịch vụ của VPS ZicNet.</p>
            
            <table class="details-table">
                <tr>
                    <th>Mã Đơn Hàng</th>
                    <td>#{{ $order->id }}</td>
                </tr>
                <tr>
                    <th>Loại thanh toán</th>
                    <td>
                        @if($order->type === 'new') Khởi tạo mới
                        @elseif($order->type === 'renew') Gia hạn dịch vụ
                        @elseif($order->type === 'upgrade') Nâng cấp tài nguyên
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>Gói dịch vụ</th>
                    <td>{{ $order->vpsPlan->name ?? 'Không xác định' }}</td>
                </tr>
                <tr>
                    <th>Tổng tiền (VNĐ)</th>
                    <td><strong>{{ number_format($order->amount, 0, ',', '.') }}đ</strong></td>
                </tr>
                <tr>
                    <th>Thời gian xử lý</th>
                    <td>{{ $order->updated_at->format('d/m/Y H:i') }}</td>
                </tr>
            </table>

            @if($order->type === 'new' && $order->vpsInstance)
            <div style="margin-top: 25px; background: #f8fafc; padding: 15px; border-radius: 6px; border-left: 4px solid #6366f1;">
                <h4 style="margin-top: 0; color: #1e293b;">Thông tin máy chủ vừa khởi tạo:</h4>
                <p style="margin: 5px 0;"><strong>IP:</strong> {{ $order->vpsInstance->ip_address ?? 'Đang cấp phát...' }}</p>
                <p style="margin: 5px 0;"><strong>Tài khoản:</strong> {{ $order->vpsInstance->username ?? 'Administrator' }}</p>
                <p style="margin: 5px 0;"><strong>Mật khẩu:</strong> {{ $order->vpsInstance->password ?? '****** (Xem trên web)' }}</p>
                <p style="margin: 5px 0;"><strong>Hệ điều hành:</strong> {{ $order->vpsInstance->os }}</p>
            </div>
            @endif

            <div style="text-align: center;">
                <a href="{{ route('client.vps.index') }}" class="btn">Quản lý VPS của tôi</a>
            </div>
        </div>
        <div class="footer">
            Đây là email tự động từ hệ thống VPS ZicNet. Vui lòng không trả lời qua email này.<br>
            Nếu bạn cần hỗ trợ, hãy gửi Ticket trực tiếp trên Website.
        </div>
    </div>
</body>
</html>
