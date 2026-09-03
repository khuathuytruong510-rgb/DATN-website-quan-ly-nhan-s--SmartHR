@extends('layouts.app')

@section('title', 'Phân quyền')

@section('content')
    <div class="page-head">
        <div>
            <h1>Phân quyền</h1>
        </div>
        <div class="page-actions">
            <a class="btn" href="{{ route('accounts.index') }}">Xem danh sách tài khoản</a>
        </div>
    </div>

    <div class="card">
        @if ($users->isEmpty())
            <div class="empty">Chưa có tài khoản nào để phân quyền.</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Họ tên</th>
                        <th>Email</th>
                        <th>Admin</th>
                        <th>Giám đốc</th>
                        <th>HR</th>
                        <th>Kế toán</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->is_admin ? 'Có' : 'Không' }}</td>
                            <td>{{ $user->is_director ? 'Có' : 'Không' }}</td>
                            <td>{{ $user->is_hr ? 'Có' : 'Không' }}</td>
                            <td>{{ $user->is_accountant ? 'Có' : 'Không' }}</td>
                            <td>
                                <form method="POST" action="{{ route('permissions.update', $user) }}" class="table-actions">
                                    @csrf
                                    @method('PUT')
                                    <label class="check-row">
                                        <input type="checkbox" name="is_admin" value="1" {{ $user->is_admin ? 'checked' : '' }}>
                                        Admin
                                    </label>
                                    <label class="check-row" title="Role Giám đốc phải đi theo người đang giữ chức">
                                        <input type="checkbox" name="is_director" value="1" {{ $user->is_director ? 'checked' : '' }} disabled>
                                        Giám đốc
                                    </label>
                                    @if($user->is_director)
                                        <input type="hidden" name="is_director" value="1">
                                    @endif
                                    <label class="check-row">
                                        <input type="checkbox" name="is_hr" value="1" {{ $user->is_hr ? 'checked' : '' }}>
                                        HR
                                    </label>
                                    <label class="check-row">
                                        <input type="checkbox" name="is_accountant" value="1" {{ $user->is_accountant ? 'checked' : '' }}>
                                        Kế toán
                                    </label>
                                    <button class="btn primary" type="submit">Lưu</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="pagination">
                {{ $users->links() }}
            </div>
        @endif
    </div>
@endsection
