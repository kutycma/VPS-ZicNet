@extends('layouts.admin')
@section('title', 'Gói VPS')
@section('page_title', 'Quản lý gói VPS')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Danh sách gói VPS</h3>
        <a href="{{ route('admin.plans.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> Thêm gói</a>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-hover">
            <thead><tr><th>Tên gói</th><th>NCC</th><th>Nhóm</th><th>Loại</th><th>CPU</th><th>RAM</th><th>Disk</th><th>Giá vốn</th><th>Giá bán</th><th>TT</th><th>Thao tác</th></tr></thead>
            <tbody>
                @forelse($plans as $plan)
                <tr>
                    <td><strong>{{ $plan->name }}</strong></td>
                    <td>{{ $plan->provider->name ?? '—' }}</td>
                    <td>@if($plan->group)<span class="badge badge-light"><i class="{{ $plan->group->icon }} mr-1"></i>{{ $plan->group->name }}</span>@else<span class="text-muted">—</span>@endif</td>
                    <td><span class="badge badge-{{ $plan->type === 'vps_vn' ? 'primary' : 'warning' }}">{{ strtoupper(str_replace('_', ' ', $plan->type)) }}</span></td>
                    <td>{{ $plan->cpu_cores }} vCPU</td>
                    <td>{{ $plan->ram_gb }} GB</td>
                    <td>{{ $plan->disk_gb }} GB</td>
                    <td>{{ number_format($plan->provider_price, 0, ',', '.') }}đ</td>
                    <td>
                        <strong style="color:#10b981;">{{ number_format($plan->display_price, 0, ',', '.') }}đ</strong>
                        <small class="text-muted">{{ $plan->display_price_cycle }}</small>
                    </td>
                    <td><span class="badge badge-{{ $plan->status === 'active' ? 'success' : 'danger' }}">{{ $plan->status }}</span></td>
                    <td>
                        <form action="{{ route('admin.plans.toggle-status', $plan) }}" method="POST" style="display:inline;">
                            @csrf @method('PATCH')
                            <button class="btn btn-sm {{ $plan->status === 'active' ? 'btn-outline-secondary' : 'btn-outline-success' }}" title="{{ $plan->status === 'active' ? 'Ẩn gói này' : 'Hiện gói này' }}">
                                <i class="fas {{ $plan->status === 'active' ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                            </button>
                        </form>
                        <a href="{{ route('admin.plans.edit', $plan) }}" class="btn btn-sm btn-outline-warning"><i class="fas fa-edit"></i></a>
                        <form action="{{ route('admin.plans.destroy', $plan) }}" method="POST" style="display:inline;" onsubmit="return confirm('Xóa gói này?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" class="text-center py-4 text-muted">Chưa có gói VPS nào</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $plans->links() }}
    </div>
</div>
@endsection
