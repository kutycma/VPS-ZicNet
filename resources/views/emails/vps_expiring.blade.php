<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Arial', sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px; color: #333; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #f1f5f9; padding-bottom: 20px; }
        .logo { font-size: 24px; font-weight: bold; color: #6366f1; }
        .content { font-size: 16px; line-height: 1.6; }
        .details-table { width: 100%; border-collapse: collapse; margin-top: 20px; border: 1px solid #fecaca; }
        .details-table th, .details-table td { padding: 12px; border-bottom: 1px solid #fee2e2; text-align: left; }
        .details-table th { background-color: #fef2f2; color: #dc2626; font-weight: 600; width: 40%; }
        .btn { display: inline-block; padding: 12px 24px; background-color: #ef4444; color: white !important; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 25px; }
        .footer { text-align: center; margin-top: 30px; font-size: 13px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">VPS ZicNet</div>
            <h2 style="color: #ef4444;">Dịch vụ VPS Sắp hết hạn!</h2>
        </div>
        <div class="content">
            <p>Chào <strong>{{ $vps->user->name }}</strong>,</p>
            <p>Hệ thống ghi nhận dịch vụ máy chủ ảo VPS của bạn sẽ hết hạn trong thời gian ngắn tới. Để tránh bị gián đoạn hoạt động hoặc mất dữ liệu, vui lòng gia hạn dịch vụ trước hạn chót.</p>
            
            <table class="details-table">
                <tr>
                    <th>Dịch Vụ VPS</th>
                    <td><strong>{{ $vps->plan->name ?? 'VPS' }}</strong> (#{{ $vps->vps_provider_id ?? $vps->id }})</td>
                </tr>
                <tr>
                    <th>IP Máy chủ</th>
                    <td>{{ $vps->ip_address ?? 'Chưa rõ' }}</td>
                </tr>
                <tr>
                    <th>Ngày hết hạn</th>
                    <td><strong>{{ \Carbon\Carbon::parse($vps->expires_at)->format('d/m/Y H:i') }}</strong></td>
                </tr>
                <tr>
                    <th>Trạng thái hiện tại</th>
                    <td><span style="color: #f59e0b; font-weight: bold;">Sắp hết hạn</span></td>
                </tr>
            </table>

            <div style="text-align: center;">
                <a href="{{ route('client.vps.show', $vps) }}" class="btn">Gia Hạn Ngay</a>
            </div>
            
            <p style="margin-top: 20px; font-size: 14px; color: #64748b;">
                <strong>Lưu ý:</strong> Quá hạn, hệ thống nhà cung cấp sẽ tự động đình chỉ máy chủ của bạn và xoá vĩnh viễn dữ liệu nếu không được gia hạn kịp thời.
            </p>
        </div>
        <div class="footer">
            Đây là email tự động từ hệ thống VPS ZicNet. Vui lòng không trả lời qua email này.<br>
            Nếu bạn đã gia hạn thành công, xin vui lòng bỏ qua email này.
        </div>
    </div>
</body>
</html>
