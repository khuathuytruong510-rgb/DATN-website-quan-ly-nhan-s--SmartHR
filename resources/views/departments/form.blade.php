@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <h1>{{ $department->exists ? 'Chỉnh sửa phòng ban' : 'Tạo phòng ban' }}</h1>
        <p class="muted">Nhập thông tin phòng ban và quản lý trưởng phòng.</p>
    </div>
    <a class="btn link" href="{{ route('departments.index') }}">Quay lại</a>
</div>

<div class="card">
    <form method="POST" action="{{ $department->exists ? route('departments.update', $department) : route('departments.store') }}">
        @csrf
        @if($department->exists)
            @method('PUT')
        @endif

        <div class="field">
            <label>Tên phòng ban</label>
            <input type="text" name="name" value="{{ old('name', $department->name) }}" required>
            @error('name')<span class="error">{{ $message }}</span>@enderror
        </div>

        <div class="field">
            <label>Mã phòng ban</label>
            <input type="text" name="code" value="{{ old('code', $department->code) }}" maxlength="10" required placeholder="VD: BGD, HR, IT...">
            @error('code')<span class="error">{{ $message }}</span>@enderror
        </div>

        <div class="field">
            <label>Trưởng phòng</label>
            <input type="text" name="manager" value="{{ old('manager', $department->manager) }}">
            @error('manager')<span class="error">{{ $message }}</span>@enderror
        </div>

        <div class="field">
            <label>Mô tả</label>
            <textarea name="description">{{ old('description', $department->description) }}</textarea>
            @error('description')<span class="error">{{ $message }}</span>@enderror
        </div>

        <div class="actions">
            <button class="btn primary" type="submit">Lưu</button>
            <a class="btn" href="{{ route('departments.index') }}">Hủy</a>
        </div>
    </form>
</div>
@endsection
