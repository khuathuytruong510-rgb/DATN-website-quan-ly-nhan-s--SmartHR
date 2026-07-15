@extends('layouts.app')

@section('title', 'Tạo yêu cầu tăng ca')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('me.dashboard') }}">Dashboard</a></li>
<li><a href="{{ route('me.overtime_requests') }}">Đăng ký tăng ca</a></li>
<li>Tạo yêu cầu</li>
@endsection
    <div class="page-head">
        <div>
            <h1>Tạo yêu cầu tăng ca</h1>
            <p class="muted">Gửi yêu cầu tăng ca tới quản lý.</p>
        </div>
        <div class="actions">
            <a class="btn" href="{{ route('me.overtime_requests') }}">Quay lại</a>
        </div>
    </div>

    <div class="card">
        <form method="POST" action="{{ route('me.overtime_requests.store') }}">
            @csrf

            <div class="field">
                <label>Ngày</label>
                <input type="date" name="date" value="{{ old('date') }}" required>
                @error('date')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="grid two-cols">
                <div>
                    <label>Giờ bắt đầu</label>
                    <input type="time" name="start_time" value="{{ old('start_time') }}" required>
                    @error('start_time')<div class="error">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label>Giờ kết thúc</label>
                    <input type="time" name="end_time" value="{{ old('end_time') }}" required>
                    @error('end_time')<div class="error">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="field">
                <label>Lý do</label>
                <textarea name="reason">{{ old('reason') }}</textarea>
                @error('reason')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="actions">
                <button class="btn primary" type="submit">Gửi yêu cầu</button>
            </div>
        </form>
    </div>
@endsection
