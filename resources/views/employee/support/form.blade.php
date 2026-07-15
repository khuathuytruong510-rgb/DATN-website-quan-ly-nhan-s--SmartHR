@extends('layouts.app')

@section('title', 'Tạo yêu cầu hỗ trợ')

@section('breadcrumb')
<li><a href="{{ route('me.dashboard') }}">Dashboard</a></li>
<li><a href="{{ route('me.support_requests') }}">Yêu cầu hỗ trợ</a></li>
<li>Tạo yêu cầu</li>
@endsection
@section('content')
<div class="page-head">
    <div>
        <h1>Tạo yêu cầu hỗ trợ</h1>
        <p class="muted">Gửi yêu cầu tới phòng nhân sự</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('me.support_requests') }}">Quay lại</a>
    </div>
</div>

<div class="card">
    <form method="POST" action="{{ route('me.support_requests.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="field">
            <label>Tiêu đề</label>
            <input name="subject" value="{{ old('subject') }}">
            @error('subject')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label>Loại</label>
            <select name="type">
                <option value="payroll">Lỗi lương</option>
                <option value="attendance">Lỗi chấm công</option>
                <option value="document">Xin giấy tờ</option>
                <option value="other">Khác</option>
            </select>
            @error('type')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label>Nội dung</label>
            <textarea name="message">{{ old('message') }}</textarea>
            @error('message')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="field">
            <label>Đính kèm</label>
            <input type="file" name="attachment">
            @error('attachment')<div class="error">{{ $message }}</div>@enderror
        </div>

        <div class="actions">
            <button class="btn primary" type="submit">Gửi</button>
        </div>
    </form>
</div>

@endsection
