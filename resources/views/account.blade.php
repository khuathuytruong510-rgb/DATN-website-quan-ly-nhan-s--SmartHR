@extends('layouts.app')

@section('title', 'Tài khoản của tôi - SmartHR')

@section('content')
<div class="page-head">
    <div>
        <h1>Tài khoản của tôi</h1>
    </div>
</div>

<div class="card">
    <form method="POST" action="{{ route('account.update') }}">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-12 col-md-6">
                <div class="field">
                    <label class="form-label" for="name">Họ tên</label>
                    <input id="name" class="form-control" name="name" type="text" value="{{ old('name', auth()->user()->name) }}">
                    @error('name')<div class="error">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="field">
                    <label class="form-label" for="email">Email</label>
                    <input id="email" class="form-control" name="email" type="email" value="{{ old('email', auth()->user()->email) }}">
                    @error('email')<div class="error">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="field">
                    <label class="form-label" for="password">Mật khẩu mới</label>
                    <input id="password" class="form-control" name="password" type="password" autocomplete="new-password">
                    @error('password')<div class="error">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="field">
                    <label class="form-label" for="password_confirmation">Xác nhận mật khẩu</label>
                    <input id="password_confirmation" class="form-control" name="password_confirmation" type="password" autocomplete="new-password">
                </div>
            </div>
        </div>

        <div class="actions" style="margin-top: 10px;">
            <button class="btn primary" type="submit">Lưu thay đổi</button>
            <a class="btn link" href="{{ route('dashboard') }}">Quay lại Dashboard</a>
        </div>
    </form>
</div>

@if ($employee)
    <div class="card" style="margin-top: 24px;">
        <h2>Thông tin nhân viên</h2>
        <div class="grid two-cols">
            <div class="field"><label>Tên</label><div>{{ $employee->name }}</div></div>
            <div class="field"><label>Email</label><div>{{ $employee->email }}</div></div>
            <div class="field"><label>Chức vụ</label><div>{{ $employee->position }}</div></div>
            <div class="field"><label>Phòng ban</label><div>{{ optional($employee->department)->name }}</div></div>
            <div class="field"><label>Trạng thái</label><div>{{ ucfirst($employee->status) }}</div></div>
        </div>
    </div>
@endif
@endsection
