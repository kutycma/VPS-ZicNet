@extends('layouts.admin')
@section('title', 'Quản lý Theme')
@section('page_title', 'Quản lý Giao diện')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Themes</li>
@endsection

@section('content')
<div class="mb-4 d-flex justify-content-between align-items-center">
    <p class="text-muted mb-0"><i class="fas fa-info-circle mr-1"></i> Chọn giao diện cho website. Chỉ 1 theme được kích hoạt tại 1 thời điểm.</p>
    <a href="{{ route('admin.themes.create') }}" class="btn btn-primary">
        <i class="fas fa-plus mr-1"></i> Tạo theme mới
    </a>
</div>

<div class="row">
    @foreach($themes as $theme)
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="card h-100 position-relative {{ $theme->is_active ? 'border-primary' : '' }}" style="{{ $theme->is_active ? 'border: 2px solid var(--theme-primary, #6366f1) !important;' : '' }}">
            {{-- Active Badge --}}
            @if($theme->is_active)
            <div class="position-absolute" style="top: 12px; right: 12px; z-index: 2;">
                <span class="badge" style="background: linear-gradient(135deg, var(--theme-primary, #6366f1), var(--theme-secondary, #8b5cf6)); color: #fff; font-size: 0.8rem; padding: 6px 14px; border-radius: 20px;">
                    <i class="fas fa-check-circle mr-1"></i> Đang sử dụng
                </span>
            </div>
            @endif

            {{-- Color Preview Bar --}}
            @php
                $vars = $theme->variables ?? [];
                $p = $vars['primary'] ?? '#6366f1';
                $s = $vars['secondary'] ?? '#8b5cf6';
                $ac = $vars['accent'] ?? '#f59e0b';
                $su = $vars['success'] ?? '#10b981';
                $da = $vars['danger'] ?? '#ef4444';
                $sbFrom = $vars['sidebar_bg_from'] ?? '#0f172a';
                $sbTo = $vars['sidebar_bg_to'] ?? '#1e1b4b';
            @endphp
            <div style="height: 80px; background: linear-gradient(135deg, {{ $sbFrom }}, {{ $sbTo }}); position: relative; border-radius: var(--theme-card-radius, 12px) var(--theme-card-radius, 12px) 0 0; overflow: hidden;">
                <div style="position: absolute; bottom: 10px; left: 16px; display: flex; gap: 6px;">
                    <span style="width: 24px; height: 24px; border-radius: 50%; background: {{ $p }}; border: 2px solid rgba(255,255,255,0.3); box-shadow: 0 2px 6px rgba(0,0,0,0.2);"></span>
                    <span style="width: 24px; height: 24px; border-radius: 50%; background: {{ $s }}; border: 2px solid rgba(255,255,255,0.3); box-shadow: 0 2px 6px rgba(0,0,0,0.2);"></span>
                    <span style="width: 24px; height: 24px; border-radius: 50%; background: {{ $ac }}; border: 2px solid rgba(255,255,255,0.3); box-shadow: 0 2px 6px rgba(0,0,0,0.2);"></span>
                    <span style="width: 24px; height: 24px; border-radius: 50%; background: {{ $su }}; border: 2px solid rgba(255,255,255,0.3); box-shadow: 0 2px 6px rgba(0,0,0,0.2);"></span>
                    <span style="width: 24px; height: 24px; border-radius: 50%; background: {{ $da }}; border: 2px solid rgba(255,255,255,0.3); box-shadow: 0 2px 6px rgba(0,0,0,0.2);"></span>
                </div>
            </div>

            <div class="card-body">
                <h5 class="font-weight-bold mb-1">
                    {{ $theme->name }}
                    @if($theme->is_default)
                        <span class="badge badge-secondary ml-1" style="font-size: 0.65rem;">MẶC ĐỊNH</span>
                    @endif
                </h5>
                <p class="text-muted mb-3" style="font-size: 0.85rem; min-height: 40px;">{{ $theme->description ?? 'Không có mô tả.' }}</p>
                
                {{-- Quick Info --}}
                <div class="d-flex align-items-center justify-content-between mb-3" style="font-size: 0.8rem; color: #8e8e93;">
                    <span><i class="fas fa-palette mr-1"></i> {{ count($theme->variables ?? []) }} biến CSS</span>
                    <span><i class="fas fa-clock mr-1"></i> {{ $theme->updated_at->diffForHumans() }}</span>
                </div>
            </div>

            <div class="card-footer bg-transparent d-flex gap-2" style="gap: 8px;">
                @if(!$theme->is_active)
                <form action="{{ route('admin.themes.activate', $theme) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Kích hoạt theme {{ $theme->name }}?')">
                        <i class="fas fa-check mr-1"></i> Kích hoạt
                    </button>
                </form>
                @endif
                
                <a href="{{ route('admin.themes.edit', $theme) }}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-edit mr-1"></i> Sửa
                </a>

                @if(!$theme->is_default && !$theme->is_active)
                <form action="{{ route('admin.themes.destroy', $theme) }}" method="POST" class="d-inline ml-auto">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xoá theme {{ $theme->name }}?')">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>

@if($themes->isEmpty())
<div class="text-center py-5">
    <i class="fas fa-palette fa-3x text-muted mb-3" style="opacity:0.3;"></i>
    <p class="text-muted">Chưa có theme nào. Hãy tạo theme đầu tiên!</p>
</div>
@endif
@endsection
