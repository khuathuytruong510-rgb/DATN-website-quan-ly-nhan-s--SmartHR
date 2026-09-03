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
                <input type="date" name="date" value="{{ old('date') }}" min="{{ now()->toDateString() }}" max="{{ now()->addDay()->toDateString() }}" required>
                @error('date')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <div class="field">
                        <label class="form-label">Giờ bắt đầu dự kiến</label>
                        <input class="form-control" type="time" name="start_time" value="{{ old('start_time', '17:30') }}" required>
                        @error('start_time')<div class="error">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="field">
                        <label class="form-label">Giờ kết thúc dự kiến</label>
                        <input class="form-control" type="time" name="end_time" value="{{ old('end_time', '20:00') }}" required>
                        @error('end_time')<div class="error">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="field">
                <label>Lý do / công việc</label>
                <textarea name="reason" required>{{ old('reason') }}</textarea>
                @error('reason')<div class="error">{{ $message }}</div>@enderror
            </div>

            <div class="actions">
                <button class="btn primary" type="submit">Gửi yêu cầu</button>
            </div>
        </form>
    </div>
@endsection
