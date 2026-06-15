@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ $contract->exists ? 'Chỉnh sửa hợp đồng' : 'Tạo hợp đồng' }}</h1>

    <form method="POST" action="{{ $contract->exists ? route('contracts.update', $contract) : route('contracts.store') }}">
        @csrf
        @if($contract->exists)
            @method('PUT')
        @endif

        <div>
            <label>Nhân viên</label>
            <select name="employee_id" required>
                <option value="">-- Chọn --</option>
                @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" {{ old('employee_id', $contract->employee_id) == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label>Tiêu đề</label>
            <input type="text" name="title" value="{{ old('title', $contract->title) }}" required>
        </div>

        <div>
            <label>Lương</label>
            <input type="number" name="salary" value="{{ old('salary', $contract->salary) }}" required>
        </div>

        <div>
            <label>Ngày bắt đầu</label>
            <input type="date" name="start_date" value="{{ old('start_date', $contract->start_date) }}" required>
        </div>

        <div>
            <label>Ngày kết thúc</label>
            <input type="date" name="end_date" value="{{ old('end_date', $contract->end_date) }}" required>
        </div>

        <div>
            <label>Trạng thái</label>
            <select name="status" required>
                <option value="active" {{ old('status', $contract->status) == 'active' ? 'selected' : '' }}>Active</option>
                <option value="pending" {{ old('status', $contract->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="expired" {{ old('status', $contract->status) == 'expired' ? 'selected' : '' }}>Expired</option>
            </select>
        </div>

        <button type="submit">Lưu</button>
        <a href="{{ route('contracts.index') }}">Hủy</a>
    </form>
</div>
@endsection
