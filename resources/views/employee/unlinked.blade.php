@extends('layouts.app')

@section('title', 'Chưa gắn hồ sơ nhân viên')

@section('content')
<div class="page-head">
    <div>
        <h1>Tài khoản chưa được liên kết với hồ sơ nhân viên</h1>
    </div>
</div>

<div class="card" style="max-width:640px;">
    <p>Cổng nhân viên (chấm công, nghỉ phép, bảng lương) chỉ mở khi HR đã tạo hồ sơ và gắn tài khoản của bạn.</p>
    <form method="POST" action="{{ route('logout') }}" style="margin-top:16px;">
        @csrf
        <button type="submit" class="btn">Đăng xuất</button>
    </form>
</div>
@endsection
