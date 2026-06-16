@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <h1>{{ $contract->exists ? 'Chỉnh sửa hợp đồng' : 'Tạo hợp đồng' }}</h1>
        <p class="muted">Quản lý thông tin hợp đồng nhân viên.</p>
    </div>
    <a class="btn link" href="{{ route('contracts.index') }}">Quay lại</a>
</div>

<div class="card">
    <form method="POST" action="{{ $contract->exists ? route('contracts.update', $contract) : route('contracts.store') }}">
        @csrf
        @if($contract->exists)
            @method('PUT')
        @endif

        <div class="field">
            <label>Nhân viên</label>
            <select name="employee_id" required>
                <option value="">-- Chọn nhân viên --</option>
                @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" {{ old('employee_id', $contract->employee_id) == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                @endforeach
            </select>
            @error('employee_id')<span class="error">{{ $message }}</span>@enderror
        </div>

        <div class="field">
            <label>Tiêu đề</label>
            <input type="text" name="title" value="{{ old('title', $contract->title) }}" required>
            @error('title')<span class="error">{{ $message }}</span>@enderror
        </div>

        <div class="field">
            <label>Lương</label>
            <input type="number" name="salary" value="{{ old('salary', $contract->salary) }}" required>
            @error('salary')<span class="error">{{ $message }}</span>@enderror
        </div>

        <div class="two-cols">
            <div class="field">
                <label>Ngày bắt đầu</label>
                <input type="date" name="start_date" value="{{ old('start_date', $contract->start_date) }}" required>
                @error('start_date')<span class="error">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label>Ngày kết thúc</label>
                <input type="date" name="end_date" value="{{ old('end_date', $contract->end_date) }}" required>
                @error('end_date')<span class="error">{{ $message }}</span>@enderror
            </div>
        </div>

        <div class="field">
            <label>Trạng thái</label>
            <select name="status" required>
                <option value="active" {{ old('status', $contract->status) == 'active' ? 'selected' : '' }}>Active</option>
                <option value="pending" {{ old('status', $contract->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="expired" {{ old('status', $contract->status) == 'expired' ? 'selected' : '' }}>Expired</option>
            </select>
            @error('status')<span class="error">{{ $message }}</span>@enderror
        </div>

        <div class="actions">
            <button class="btn primary" type="submit">Lưu</button>
            <a class="btn" href="{{ route('contracts.index') }}">Hủy</a>
        </div>
    </form>
</div>
@endsection
