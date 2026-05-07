@extends('layouts.client')
@section('title', 'Nâng Cấp VPS')
@section('page_title', 'Mua Thêm Tài Nguyên VPS #' . ($instance->vps_provider_id ?? $instance->id))
@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card" style="border-radius:16px;">
            <div class="card-header"><h3 class="card-title text-warning"><i class="fas fa-arrow-circle-up mr-2"></i>Nâng Cấp Cấu Hình</h3></div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Số dư của bạn:</strong> <span class="badge badge-primary" style="font-size:1rem;">{{ number_format(Auth::user()->balance ?? 0) }} VNĐ</span>
                </div>
                
                @if(empty($addons))
                    <div class="alert alert-warning">Hiện tại không có thông tin báo giá cấu hình nâng cấp từ nhà cung cấp. Vui lòng thử lại sau.</div>
                    <a href="{{ route('client.vps.show', $instance) }}" class="btn btn-secondary">Quay lại</a>
                @else
                    <form action="{{ route('client.vps.upgrade.confirm', $instance) }}" method="POST" id="upgradeForm">
                        @csrf
                        <input type="hidden" name="total_price" id="hiddenTotalPrice" value="0">
                        <div class="table-responsive">
                        <table class="table mb-4">
                            <thead>
                                <tr>
                                    <th>Cấu hình cần mua thêm</th>
                                    <th>Đơn giá (Chu kỳ)</th>
                                    <th width="150">Số lượng cọng thêm</th>
                                    <th class="text-right">Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($addons['cpu']))
                                    <tr>
                                        <td><strong>+ CPU Cores</strong><div class="text-muted small">Tăng số lượng CPU</div></td>
                                        <td>{{ number_format($addons['cpu']['selling_price'] ?? 0) }}đ</td>
                                        <td>
                                            <input type="number" name="addon_cpu" id="addon_cpu" class="form-control text-center addon-input" min="0" value="0" data-price="{{ $addons['cpu']['selling_price'] ?? 0 }}" data-step="1">
                                        </td>
                                        <td class="text-right"><strong id="sum_cpu">0đ</strong></td>
                                    </tr>
                                @endif
                                
                                @if(isset($addons['ram']))
                                    <tr>
                                        <td><strong>+ RAM (GB)</strong><div class="text-muted small">Tăng dung lượng RAM</div></td>
                                        <td>{{ number_format($addons['ram']['selling_price'] ?? 0) }}đ</td>
                                        <td>
                                            <input type="number" name="addon_ram" id="addon_ram" class="form-control text-center addon-input" min="0" value="0" data-price="{{ $addons['ram']['selling_price'] ?? 0 }}" data-step="1">
                                        </td>
                                        <td class="text-right"><strong id="sum_ram">0đ</strong></td>
                                    </tr>
                                @endif
                                
                                @if(isset($addons['disk']))
                                    <tr>
                                        <td><strong>+ Disk (GB)</strong><div class="text-muted small">Tăng dung lượng Ổ Cứng (Bội số của 10)</div></td>
                                        <td>{{ number_format($addons['disk']['selling_price'] ?? 0) }}đ<small> / 10GB</small></td>
                                        <td>
                                            <input type="number" name="addon_disk" id="addon_disk" class="form-control text-center addon-input" min="0" step="10" value="0" data-price="{{ $addons['disk']['selling_price'] ?? 0 }}" data-step="10">
                                        </td>
                                        <td class="text-right"><strong id="sum_disk">0đ</strong></td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                        </div>

                        <div class="form-group p-3 bg-light rounded text-center">
                            <h4 class="mb-0">Tổng thanh toán: <strong id="totalPrice" class="text-success">0 VNĐ</strong></h4>
                        </div>

                        <div class="text-center mt-4 pt-3 border-top">
                            <a href="{{ route('client.vps.show', $instance) }}" class="btn btn-secondary btn-lg mr-2" style="border-radius:10px;"><i class="fas fa-arrow-left mr-2"></i>Hủy</a>
                            <button type="submit" class="btn btn-warning btn-lg" style="border-radius:10px;" id="btnSubmit" onclick="return confirm('Xác nhận thanh toán và nâng cấp cấu hình VPS? (Yêu cầu Khởi động lại máy chủ)')"><i class="fas fa-rocket mr-2"></i>Thanh Toán Ngay</button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const userBalance = {{ Auth::user()->balance ?? 0 }};
    const inputs = document.querySelectorAll('.addon-input');
    
    function calculateTotal() {
        let total = 0;
        
        inputs.forEach(input => {
            let val = parseInt(input.value) || 0;
            let price = parseInt(input.getAttribute('data-price')) || 0;
            let step = parseInt(input.getAttribute('data-step')) || 1;
            
            // Check step validation for disk
            if (input.id === 'addon_disk') {
                // val must be multiple of 10
                if (val % 10 !== 0) {
                    val = Math.floor(val / 10) * 10;
                    input.value = val;
                }
            }
            
            let lineTotal = val > 0 ? (val / step) * price : 0;
            document.getElementById('sum_' + input.id.replace('addon_', '')).innerText = lineTotal.toLocaleString('vi-VN') + 'đ';
            total += lineTotal;
        });
        
        document.getElementById('hiddenTotalPrice').value = total;
        document.getElementById('totalPrice').innerText = total.toLocaleString('vi-VN') + ' VNĐ';
        
        const btn = document.getElementById('btnSubmit');
        if (total === 0) {
            btn.disabled = true;
            document.getElementById('totalPrice').className = "text-muted";
        } else if (total > userBalance) {
            document.getElementById('totalPrice').innerText += " (Không đủ số dư)";
            document.getElementById('totalPrice').className = "text-danger";
            btn.disabled = true;
        } else {
            document.getElementById('totalPrice').className = "text-success";
            btn.disabled = false;
        }
    }
    
    inputs.forEach(input => {
        input.addEventListener('change', calculateTotal);
        input.addEventListener('keyup', calculateTotal);
    });
    
    // Init state
    calculateTotal();
</script>
@endpush
@endsection
