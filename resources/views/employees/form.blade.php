@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ $employee->exists ? 'Chỉnh sửa nhân viên' : 'Tạo nhân viên' }}</h1>

    <form method="POST" action="{{ $employee->exists ? route('employees.update', $employee) : route('employees.store') }}">
        @csrf
        @if($employee->exists)
            @method('PUT')
        @endif

        <div>
            <label>Tên</label>
            <input type="text" name="name" value="{{ old('name', $employee->name) }}" required>
        </div>

        <div>
            <label>Email</label>
            <input type="email" name="email" value="{{ old('email', $employee->email) }}" required>
        </div>

        <div>
            <label>Chức vụ</label>
            <input type="text" name="position" value="{{ old('position', $employee->position) }}" required>
        </div>

        <div>
            <label>Phòng ban</label>
            <select name="department_id" required>
                <option value="">-- Chọn --</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ old('department_id', $employee->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label>Trạng thái</label>
            <select name="status" required>
                <option value="active" {{ old('status', $employee->status) == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ old('status', $employee->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>

        <button type="submit">Lưu</button>
        <a href="{{ route('employees.index') }}">Hủy</a>
    </form>
</div>
@endsection
