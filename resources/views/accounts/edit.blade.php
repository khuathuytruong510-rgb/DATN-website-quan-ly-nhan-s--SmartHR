@extends('layouts.app')

@section('content')
<div class="card">

    <h2>Sửa tài khoản</h2>

    <form method="POST"
          action="{{ route('accounts.update',$user) }}">
        @csrf
        @method('PUT')

        <input type="text"
               name="name"
               value="{{ $user->name }}">

        <input type="email"
               name="email"
               value="{{ $user->email }}">

        <select name="role">

            <option value="employee"
                {{ $user->role=='employee'?'selected':'' }}>
                Nhân viên
            </option>

            <option value="hr"
                {{ $user->role=='hr'?'selected':'' }}>
                HR
            </option>

            <option value="admin"
                {{ $user->role=='admin'?'selected':'' }}>
                Admin
            </option>

        </select>

        <button class="btn primary">
            Lưu thay đổi
        </button>

    </form>

</div>
@endsection