@extends('layouts.admin')
@section('title', 'Nâng Cấp VPS (Admin Cấp)')
@section('page_title', 'Mua Thêm Tài Nguyên VPS #' . ($instance->vps_provider_id ?? $instance->id))
@section('content')
<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card" style="border-radius:16px;">
            <div class="card-header"><h3 class="card-title text-warning"><i class="fas fa-arrow-circle-up mr-2"></i>Nâng Cấp Cấu Hình</h3></div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Tài khoản người dùng:</strong> {{ $instance->user->name }} ({{ collect(explode(' ', $instance->user->email))->first() }})<br>
                    <strong>Số dư hiện có:</strong> <span class="badge badge-primary" style="font-size:1rem;">{{ number_format($instance->user->balance ?? 0) }} VNĐ</span>
                </div>
                
                @if(empty($addons))
                    <div class="alert alert-warning">Hiện tại không có thông tin báo giá cấu hình nâng cấp từ nhà cung cấp. Vui lòng thử lại sau.</div>
                    <a href="{{ route('admin.vps.show', $instance) }}" class="btn btn-secondary">Quay lại</a>
                @else
                    <form action="{{ route('admin.vps.upgrade', $instance) }}" method="POST" id="upgradeForm">
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
                            @else
                                <tr>
                                    <td><strong>+ CPU Cores</strong><div class="text-muted small">Tăng số lượng CPU</div></td>
                                    <td>-</td>
                                    <td><input type="number" name="addon_cpu" id="addon_cpu" class="form-control text-center addon-input" min="0" value="0" data-price="0" data-step="1"></td>
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
                            @else
                                <tr>
                                    <td><strong>+ RAM (GB)</strong><div class="text-muted small">Tăng dung lượng RAM</div></td>
                                    <td>-</td>
                                    <td><input type="number" name="addon_ram" id="addon_ram" class="form-control text-center addon-input" min="0" value="0" data-price="0" data-step="1"></td>
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
                            @else
                                <tr>
                                    <td><strong>+ Disk (GB)</strong><div class="text-muted small">Tăng dung lượng Ổ Cứng (Bội số của 10)</div></td>
                                    <td>-</td>
                                    <td><input type="number" name="addon_disk" id="addon_disk" class="form-control text-center addon-input" min="0" step="10" value="0" data-price="0" data-step="10"></td>
                                    <td class="text-right"><strong id="sum_disk">0đ</strong></td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                    </div>

                    <div class="form-group mb-4 p-3 bg-light rounded text-center">
                        <div class="mb-3">
                            <span class="text-muted">Tổng giá gốc ước tính: </span>
                            <strong id="originalPriceLabel" class="text-success" style="font-size: 1.2rem;">0 VNĐ</strong>
                        </div>
                        <label for="custom_amount" class="font-weight-bold d-block">Số tiền sẽ thu của Khách (VNĐ):</label>
                        <input type="number" name="custom_amount" id="custom_amount" class="form-control form-control-lg d-inline-block text-center" style="max-width:300px; font-weight:bold" min="0" required value="0">
                        <div class="form-text text-muted small mt-2">Hệ thống sẽ trừ số tiền này vào tài khoản của khách. Có thể sửa (giảm giá/miễn phí).</div>
                        @error('custom_amount') <span class="text-danger mt-1 d-block">{{ $message }}</span> @enderror
                    </div>

                    <div class="text-center mt-4 pt-3 border-top">
                        <a href="{{ route('admin.vps.show', $instance) }}" class="btn btn-secondary btn-lg mr-2" style="border-radius:10px;"><i class="fas fa-arrow-left mr-2"></i>Hủy</a>
                        <button type="submit" class="btn btn-warning btn-lg" style="border-radius:10px;" id="btnSubmit" onclick="return confirm('Xác nhận CẤU HÌNH VÀ THU TIỀN khách?')"><i class="fas fa-rocket mr-2"></i>Thực Hiện Nâng Cấp</button>
                    </div>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const userBalance = {{ $instance->user->balance ?? 0 }};
    const inputs = document.querySelectorAll('.addon-input');
    
    function calculateTotal() {
        let total = 0;
        
        inputs.forEach(input => {
            let val = parseInt(input.value) || 0;
            let price = parseInt(input.getAttribute('data-price')) || 0;
            let step = parseInt(input.getAttribute('data-step')) || 1;
            
            // Check step validation for disk
            if (input.id === 'addon_disk') {
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
        document.getElementById('originalPriceLabel').innerText = total.toLocaleString('vi-VN') + ' VNĐ';
        document.getElementById('custom_amount').value = total;
        
        const btn = document.getElementById('btnSubmit');
        if (total === 0) {
            btn.disabled = true;
        } else {
            btn.disabled = false;
        }
    }
    
    inputs.forEach(input => {
        input.addEventListener('change', calculateTotal);
        input.addEventListener('keyup', calculateTotal);
    });
    
    calculateTotal();
</script>
@endpush
@endsection
