@extends('layouts.client')
@section('title', __('client.create_ticket'))
@section('page_title', __('client.create_ticket'))
@section('content')
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">{{ __('client.new_request') }}</h3>
            </div>
            <form action="{{ route('client.tickets.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="form-group mb-3">
                        <label>{{ __('client.subject') }} <span class="text-danger">*</span></label>
                        <input type="text" name="subject" class="form-control" required value="{{ old('subject') }}">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label>{{ __('client.department') }} <span class="text-danger">*</span></label>
                                <select name="department" class="form-control" required>
                                    <option value="technical" {{ old('department') == 'technical' ? 'selected' : '' }}>{{ __('client.technical') }}</option>
                                    <option value="sales" {{ old('department') == 'sales' ? 'selected' : '' }}>{{ __('client.sales') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label>{{ __('client.priority') }} <span class="text-danger">*</span></label>
                                <select name="priority" class="form-control" required>
                                    <option value="low" {{ old('priority') == 'low' ? 'selected' : '' }}>{{ __('client.priority_low') }}</option>
                                    <option value="medium" selected {{ old('priority') == 'medium' ? 'selected' : '' }}>{{ __('client.priority_medium') }}</option>
                                    <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>{{ __('client.priority_high') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label>{{ __('client.message') }} <span class="text-danger">*</span></label>
                        <textarea name="message" rows="6" class="form-control" required>{{ old('message') }}</textarea>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> {{ __('client.send') }}</button>
                    <a href="{{ route('client.tickets.index') }}" class="btn btn-default float-right">{{ __('client.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
