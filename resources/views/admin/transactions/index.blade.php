@extends('layouts.admin')
@section('title', 'Giao dịch')
@section('page_title', 'Lịch sử giao dịch')
@section('content')
<div class="card">
    <div class="card-header">
        <form class="form-inline" method="GET">
            <select name="type" class="form-control form-control-sm mr-2"><option value="">Tất cả loại</option>@foreach(['deposit','purchase','refund','renewal','admin_adjust','promotion'] as $t)<option value="{{ $t }}" {{ request('type')==$t?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$t)) }}</option>@endforeach</select>
            <input type="date" name="date_from" class="form-control form-control-sm mr-2" value="{{ request('date_from') }}">
            <input type="date" name="date_to" class="form-control form-control-sm mr-2" value="{{ request('date_to') }}">
            <button class="btn btn-primary btn-sm"><i class="fas fa-filter"></i></button>
        </form>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover">
            <thead><tr><th>#</th><th>User</th><th>Loại</th><th>Số tiền</th><th>Trước</th><th>Sau</th><th>Mô tả</th><th>Thời gian</th></tr></thead>
            <tbody>
                @foreach($transactions as $tx)
                <tr>
                    <td>{{ $tx->id }}</td>
                    <td>{{ $tx->user->name ?? 'N/A' }}</td>
                    <td><span class="badge badge-{{ $tx->type_badge }}">{{ $tx->type_label }}</span></td>
                    <td style="color:{{ $tx->amount >= 0 ? '#10b981' : '#ef4444' }};font-weight:600;">{{ $tx->formatted_amount }}</td>
                    <td>{{ number_format($tx->balance_before, 0, ',', '.') }}đ</td>
                    <td>{{ number_format($tx->balance_after, 0, ',', '.') }}đ</td>
                    <td>{{ $tx->description }}</td>
                    <td>{{ $tx->created_at->format('d/m/Y H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-3">{{ $transactions->links() }}</div>
    </div>
</div>
@endsection
