@extends('layouts.app')

@section('title', 'Bổ nhiệm Giám đốc từ bên ngoài')

@section('content')
    <div class="page-head">
        <div>
            <h1>Người mới chưa có trong SmartHR</h1>
        </div>
        <div class="page-actions">
            <a class="btn" href="{{ route('director_succession.index') }}">Quay lại người giữ chức</a>
        </div>
    </div>

    <div class="grid two-cols">
        <div class="card">
            <div class="card-head">
                <h2 class="card-title">Bước 1 — HR tạo hồ sơ</h2>
            </div>
            <div class="actions">
                <a class="btn primary" href="{{ $hrCreateUrl }}">Tạo hồ sơ nhân viên</a>
            </div>
        </div>
        <div class="card">
            <div class="card-head">
                <h2 class="card-title">Bước 2 — Admin tạo tài khoản</h2>
            </div>
            <div class="actions">
                <a class="btn primary" href="{{ route('accounts.create') }}">Tạo tài khoản</a>
                <a class="btn" href="{{ route('director_succession.index') }}">Về danh sách người giữ chức</a>
            </div>
        </div>
    </div>
@endsection
