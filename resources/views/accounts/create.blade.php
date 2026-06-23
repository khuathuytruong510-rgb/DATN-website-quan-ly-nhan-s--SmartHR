@extends('layouts.app')

@section('content')
<div class="card">
    <h2>Tạo tài khoản</h2>

    <form method="POST"
          action="{{ route('accounts.store') }}">
        @csrf

        <input type="text"
               name="name"
               placeholder="Họ tên">

        <input type="email"
               name="email"
               placeholder="Email">

        <input type="password"
               name="password"
               placeholder="Mật khẩu">

        <select name="role">
            <option value="employee">Nhân viên</option>
            <option value="hr">HR</option>
            <option value="admin">Admin</option>
        </select>

        <button class="btn primary">
            Tạo tài khoản
        </button>
    </form>
</div>
@endsection