@extends('layouts.client')
@section('title', 'Cài Lại Hệ Điều Hành')
@section('page_title', 'Cài Lại HĐH VPS #' . ($instance->vps_provider_id ?? $instance->id))
@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card" style="border-radius:16px;">
            <div class="card-header bg-danger text-white" style="border-radius:16px 16px 0 0;">
                <h3 class="card-title text-white m-0"><i class="fas fa-exclamation-triangle mr-2"></i>Cảnh báo: Cài Lại Hệ Điều Hành</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <strong>Lưu ý quan trọng:</strong> 
                    Việc cài lại hệ điều hành sẽ <b>XÓA TOÀN BỘ DỮ LIỆU</b> hiện có trên VPS của bạn. Quá trình này diễn ra tự động và không thể hoàn tác. Xin vui lòng cân nhắc kỹ trước khi thực hiện!
                </div>

                <form action="{{ route('client.vps.rebuild.confirm', $instance) }}" method="POST">
                    @csrf
                    <div class="form-group mb-4">
                        <label for="os_id" class="font-weight-bold">Chọn hệ điều hành mới:</label>
                        <select name="os_id" id="os_id" class="form-control form-control-lg" required>
                            <option value="">-- Vui lòng chọn HĐH --</option>
                            @if(is_array($osList))
                                @foreach($osList as $os)
                                    @php 
                                        $osId = $os['os-id'] ?? ($os['id'] ?? null);
                                        $osName = $os['os-name'] ?? ($os['name'] ?? 'Unknown OS');
                                    @endphp
                                    @if($osId)
                                        <option value="{{ $osId }}">{{ $osName }}</option>
                                    @endif
                                @endforeach
                            @endif
                        </select>
                        @error('os_id') <span class="text-danger mt-1 d-block">{{ $message }}</span> @enderror
                    </div>

                    <div class="text-center mt-4 pt-3 border-top">
                        <a href="{{ route('client.vps.show', $instance) }}" class="btn btn-secondary btn-lg mr-2" style="border-radius:10px;"><i class="fas fa-arrow-left mr-2"></i>Quay lại</a>
                        <button type="submit" class="btn btn-danger btn-lg" style="border-radius:10px;" onclick="return confirm('BẠN CHẮC CHẮN MUỐN XÓA TOÀN BỘ DỮ LIỆU VÀ CÀI LẠI VPS NÀY CHỨ?')"><i class="fas fa-microchip mr-2"></i>Xác nhận Cài lại OS</button>
                    </div>
                </form>

            </div>
        </div>
        
        <!-- For debugging purpose if osList format is unexpected -->
        @if(empty($osList))
            <div class="card mt-4">
                <div class="card-body bg-light text-muted">
                    <small>Raw Data Inspect: <?php // echo json_encode($result, JSON_PRETTY_PRINT); ?></small>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
