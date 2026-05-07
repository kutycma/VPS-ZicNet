@extends('layouts.client')
@section('title', __('client.buy_vps'))
@section('page_title', __('client.choose_plan'))
@section('content')

{{-- Tab Navigation --}}
@if($groups->isNotEmpty())
<div class="mb-4">
    <div class="d-flex flex-wrap" style="gap:10px;">
        <a href="{{ route('client.orders.plans') }}"
           class="btn font-weight-bold {{ !$selectedGroup ? 'btn-primary' : 'btn-outline-secondary' }}"
           style="border-radius:20px; padding:8px 20px; transition:all 0.3s; {{ !$selectedGroup ? 'background:linear-gradient(135deg,#6366f1,#8b5cf6);border:none;box-shadow:0 4px 12px rgba(99,102,241,0.3);' : '' }}">
            <i class="fas fa-th-large mr-1"></i> Tất cả
        </a>
        @foreach($groups as $group)
        <a href="{{ route('client.orders.plans', ['group' => $group->slug]) }}"
           class="btn font-weight-bold {{ $selectedGroup === $group->slug ? 'btn-primary' : 'btn-outline-secondary' }}"
           style="border-radius:20px; padding:8px 20px; transition:all 0.3s; {{ $selectedGroup === $group->slug ? 'background:linear-gradient(135deg,#6366f1,#8b5cf6);border:none;box-shadow:0 4px 12px rgba(99,102,241,0.3);' : '' }}">
            <i class="{{ $group->icon }} mr-1"></i> {{ $group->name }}
        </a>
        @endforeach
    </div>
</div>
@endif

{{-- Plans Grid --}}
@foreach($plans as $type => $typePlans)
<h4 class="mb-3 mt-4"><i class="fas fa-{{ $type === 'vps_vn' ? 'flag' : 'globe' }} mr-2" style="color:#6366f1;"></i>{{ $type === 'vps_vn' ? __('client.vps_vietnam') : __('client.vps_international') }}</h4>
<div class="row">
    @foreach($typePlans as $plan)
    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
        <div class="card h-100" style="border-radius:16px; transition: all 0.3s;" onmouseover="this.style.transform='translateY(-5px)';this.style.boxShadow='0 10px 30px rgba(99,102,241,0.15)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
            <div class="card-body text-center">
                <div style="background:linear-gradient(135deg,#6366f1,#8b5cf6); width:60px;height:60px;border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 15px;">
                    <i class="fas fa-server fa-lg text-white"></i>
                </div>
                <h5 style="font-weight:700;color:#1e293b;">{{ $plan->name }}</h5>
                @if($plan->group)
                    <span class="badge badge-light mb-2" style="font-size:0.7rem;"><i class="{{ $plan->group->icon }} mr-1"></i>{{ $plan->group->name }}</span>
                @endif
                <div class="my-3">
                    <span class="d-block"><i class="fas fa-microchip mr-1 text-muted"></i> {{ $plan->cpu_cores }} vCPU</span>
                    <span class="d-block"><i class="fas fa-memory mr-1 text-muted"></i> {{ $plan->ram_gb }} GB RAM</span>
                    <span class="d-block"><i class="fas fa-hdd mr-1 text-muted"></i> {{ $plan->disk_gb }} GB SSD</span>
                </div>
                <div class="mb-3">
                    <span style="font-size:1.8rem; font-weight:800; background:linear-gradient(135deg,#6366f1,#8b5cf6); -webkit-background-clip:text; -webkit-text-fill-color:transparent;">
                        {{ number_format($plan->display_price, 0, ',', '.') }}đ
                    </span>
                    <span class="d-block text-muted" style="font-size:0.8rem;">{{ $plan->display_price_cycle }}</span>
                </div>
                <a href="{{ route('client.orders.create', $plan) }}" class="btn btn-primary btn-block" style="border-radius:10px;">
                    <i class="fas fa-shopping-cart mr-1"></i> {{ __('client.buy_now') }}
                </a>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endforeach

@if($plans->isEmpty())
<div class="text-center py-5 text-muted"><i class="fas fa-box-open fa-3x mb-3 d-block"></i><p>{{ __('client.no_plans') }}</p></div>
@endif
@endsection
