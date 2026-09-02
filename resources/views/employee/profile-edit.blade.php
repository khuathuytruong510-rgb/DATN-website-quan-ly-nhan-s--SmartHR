@extends('layouts.app')

@section('title', 'Cập nhật thông tin cá nhân')

@section('content')
    <div class="page-head">
        <div>
            <h1>Cập nhật thông tin cá nhân</h1>
            <p class="muted">Chỉ gửi thay đổi thông tin liên hệ / giấy tờ cá nhân. Chức vụ, phòng ban, mã NV do HR quản lý.</p>
        </div>
    </div>

    <div class="card">
        <form action="{{ route('me.profile.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <div class="field">
                        <label class="form-label" for="name">Họ tên</label>
                        <input id="name" type="text" name="name" value="{{ old('name', $employee->name) }}" class="form-control" required>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="field">
                        <label class="form-label" for="gender">Giới tính</label>
                        <select id="gender" name="gender" class="form-select">
                            <option value="">Chọn giới tính</option>
                            <option value="male" {{ old('gender', $employee->gender) === 'male' ? 'selected' : '' }}>Nam</option>
                            <option value="female" {{ old('gender', $employee->gender) === 'female' ? 'selected' : '' }}>Nữ</option>
                            <option value="other" {{ old('gender', $employee->gender) === 'other' ? 'selected' : '' }}>Khác</option>
                        </select>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="field">
                        <label class="form-label" for="dob">Ngày sinh</label>
                        <input id="dob" type="date" name="dob" value="{{ old('dob', optional($employee->dob)->format('Y-m-d')) }}" class="form-control">
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="field">
                        <label class="form-label" for="phone">Số điện thoại</label>
                        <input id="phone" type="text" name="phone" value="{{ old('phone', $employee->phone) }}" class="form-control">
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="field">
                        <label class="form-label" for="address">Địa chỉ</label>
                        <input id="address" type="text" name="address" value="{{ old('address', $employee->address) }}" class="form-control">
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="field">
                        <label class="form-label" for="cccd">CCCD</label>
                        <input id="cccd" type="text" name="cccd" value="{{ old('cccd', $employee->cccd) }}" class="form-control">
                    </div>
                </div>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" class="btn btn-primary">Gửi cập nhật</button>
                <a class="btn btn-secondary" href="{{ route('me.profile') }}">Hủy</a>
            </div>
        </form>
    </div>
@endsection
