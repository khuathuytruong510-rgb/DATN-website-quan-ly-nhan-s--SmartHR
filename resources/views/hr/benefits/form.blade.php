@extends('layouts.app')

@section('title', $benefit->exists ? 'Cập nhật phúc lợi' : 'Tạo phúc lợi')

@section('content')
<div class="max-w-4xl">
    <div class="page-head">
        <div>
            <h1>{{ $benefit->exists ? 'Cập nhật phúc lợi' : 'Tạo phúc lợi mới' }}</h1>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert error">
            <h3>Lỗi xác thực</h3>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <form method="POST" action="{{ $benefit->exists ? route('benefits.update', $benefit) : route('benefits.store') }}">
            @csrf
            @if($benefit->exists)
                @method('PUT')
            @endif

            <div class="field">
                <label for="employee_id">Nhân viên <span>*</span></label>
                <select id="employee_id" name="employee_id" required>
                    <option value="">-- Chọn nhân viên --</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ old('employee_id', $benefit->employee_id) == $employee->id ? 'selected' : '' }}>
                            {{ $employee->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="code">Mã phúc lợi <span>*</span></label>
                <input id="code" name="code" value="{{ old('code', $benefit->code) }}" required>
            </div>

            <div class="field">
                <label for="title">Tiêu đề <span>*</span></label>
                <input id="title" name="title" value="{{ old('title', $benefit->title) }}" required>
            </div>

            <div class="field">
                <label for="type">Loại phúc lợi <span>*</span></label>
                <select id="type" name="type" required>
                    <option value="">-- Chọn loại --</option>
                    @foreach($types as $key => $label)
                        <option value="{{ $key }}" {{ old('type', $benefit->type) == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="amount">Số tiền</label>
                <input type="number" step="0.01" id="amount" name="amount" value="{{ old('amount', $benefit->amount) }}">
            </div>

            <div class="field">
                <label for="unit">Đơn vị tính</label>
                <input id="unit" name="unit" value="{{ old('unit', $benefit->unit) }}">
            </div>

            <div class="field">
                <label for="applies_to">Áp dụng cho</label>
                <input id="applies_to" name="applies_to" value="{{ old('applies_to', $benefit->applies_to) }}">
            </div>

            <div class="field">
                <label for="condition">Điều kiện</label>
                <textarea id="condition" name="condition">{{ old('condition', $benefit->condition) }}</textarea>
            </div>

            <div class="field">
                <label for="effective_date">Ngày hiệu lực</label>
                <input type="date" id="effective_date" name="effective_date" value="{{ old('effective_date', optional($benefit->effective_date)->toDateString()) }}">
            </div>

            <div class="field">
                <label for="expiry_date">Ngày hết hạn</label>
                <input type="date" id="expiry_date" name="expiry_date" value="{{ old('expiry_date', optional($benefit->expiry_date)->toDateString()) }}">
            </div>

            <div class="field">
                <label for="application_status">Trạng thái ứng dụng</label>
                <select id="application_status" name="application_status" required>
                    <option value="">-- Chọn trạng thái --</option>
                    @foreach($applicationStatuses as $key => $label)
                        <option value="{{ $key }}" {{ old('application_status', $benefit->application_status) == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="approval_status">Trạng thái phê duyệt</label>
                <select id="approval_status" name="approval_status" required>
                    <option value="">-- Chọn trạng thái --</option>
                    @foreach($approvalStatuses as $key => $label)
                        <option value="{{ $key }}" {{ old('approval_status', $benefit->approval_status) == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="status">Trạng thái</label>
                <select id="status" name="status" required>
                    <option value="">-- Chọn trạng thái --</option>
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" {{ old('status', $benefit->status) == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="notes">Ghi chú</label>
                <textarea id="notes" name="notes">{{ old('notes', $benefit->notes) }}</textarea>
            </div>

            <div class="actions">
                <button type="submit" class="btn primary">{{ $benefit->exists ? 'Cập nhật' : 'Tạo' }}</button>
                <a class="btn" href="{{ route('benefits.index') }}">Hủy</a>
            </div>
        </form>
    </div>
</div>
@endsection

