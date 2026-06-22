@extends('layouts.app')

@section('content')
<div class="content">
    <div class="page-head">
        <div>
            <h1>{{ $payroll->exists ? 'Chỉnh sửa lương' : 'Tạo bản ghi lương' }}</h1>
            <p class="muted">Quản lý thông tin lương nhân viên</p>
        </div>
    </div>

    @if ($errors->any())
        <div style="background: #fee2e2; border-left: 4px solid #dc2626; padding: 16px; border-radius: 8px; margin-bottom: 24px;">
            <h3 style="color: #dc2626; margin-top: 0;">Lỗi xác thực</h3>
            <ul style="margin: 10px 0 0; padding-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <form method="POST" action="{{ $payroll->exists ? route('payroll.update', $payroll) : route('payroll.store') }}">
            @csrf
            @if($payroll->exists)
                @method('PUT')
            @endif

            <div class="field">
                <label for="employee_id">Nhân viên <span style="color: #dc2626;">*</span></label>
                <select id="employee_id" name="employee_id" required>
                    <option value="">-- Chọn nhân viên --</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ old('employee_id', $payroll->employee_id) == $employee->id ? 'selected' : '' }}>
                            {{ $employee->name }}
                        </option>
                    @endforeach
                </select>
                @error('employee_id')
                    <span class="error">{{ $message }}</span>
                @enderror
            </div>

            <div class="field">
                <label for="month">Tháng <span style="color: #dc2626;">*</span></label>
                <input 
                    type="month" 
                    id="month" 
                    name="month" 
                    value="{{ old('month', $payroll->month) }}" 
                    required
                >
                @error('month')
                    <span class="error">{{ $message }}</span>
                @enderror
            </div>

            <div class="field">
                <label for="base_salary">Lương cơ bản <span style="color: #dc2626;">*</span></label>
                <input 
                    type="number" 
                    id="base_salary" 
                    name="base_salary" 
                    value="{{ old('base_salary', $payroll->base_salary) }}" 
                    min="0"
                    step="0.01"
                    required
                    placeholder="0.00"
                >
                @error('base_salary')
                    <span class="error">{{ $message }}</span>
                @enderror
            </div>

            <div class="field">
                <label for="allowance">Phụ cấp</label>
                <input 
                    type="number" 
                    id="allowance" 
                    name="allowance" 
                    value="{{ old('allowance', $payroll->allowance ?? 0) }}" 
                    min="0"
                    step="0.01"
                    placeholder="0.00"
                >
                @error('allowance')
                    <span class="error">{{ $message }}</span>
                @enderror
            </div>

            <div class="field">
                <label for="deduction">Khấu trừ</label>
                <input 
                    type="number" 
                    id="deduction" 
                    name="deduction" 
                    value="{{ old('deduction', $payroll->deduction ?? 0) }}" 
                    min="0"
                    step="0.01"
                    placeholder="0.00"
                >
                @error('deduction')
                    <span class="error">{{ $message }}</span>
                @enderror
            </div>

            <div class="field">
                <label for="status">Trạng thái</label>
                <select id="status" name="status">
                    <option value="pending" {{ old('status', $payroll->status ?? 'pending') == 'pending' ? 'selected' : '' }}>Chờ duyệt</option>
                    <option value="approved" {{ old('status', $payroll->status) == 'approved' ? 'selected' : '' }}>Đã duyệt</option>
                    <option value="paid" {{ old('status', $payroll->status) == 'paid' ? 'selected' : '' }}>Đã thanh toán</option>
                </select>
                @error('status')
                    <span class="error">{{ $message }}</span>
                @enderror
            </div>

            <div class="actions" style="margin-top: 32px;">
                <button type="submit" class="btn primary">{{ $payroll->exists ? 'Cập nhật' : 'Tạo' }}</button>
                <a href="{{ route('payroll.index') }}" class="btn">Hủy</a>
            </div>
        </form>
    </div>
</div>

<style>
    .content { max-width: 600px; }
    .card { background: var(--panel); border: 1px solid var(--line); border-radius: 12px; padding: 24px; }
    .field { margin-bottom: 20px; }
    label { display: block; font-weight: 700; margin-bottom: 8px; }
    input, select { width: 100%; padding: 11px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font: inherit; }
    .error { color: #dc2626; font-size: 13px; display: block; margin-top: 5px; }
    .actions { display: flex; gap: 12px; }
    .btn { padding: 10px 16px; border-radius: 8px; border: none; cursor: pointer; font-weight: 700; text-decoration: none; }
    .btn.primary { background: #2563eb; color: white; }
    .btn { background: #f8fafc; color: inherit; }
</style>
@endsection
