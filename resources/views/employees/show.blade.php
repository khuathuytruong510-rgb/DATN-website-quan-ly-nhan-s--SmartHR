@extends('layouts.app')

@section('content')
@php
    $directorView = auth()->user()?->is_director && ! auth()->user()?->canManageHr();
    $contractStatusLabel = function (?string $status): string {
        return match ($status) {
            'waiting_employee_signature', 'waiting_employee' => 'Chờ NV ký',
            'waiting_director_signature', 'waiting_director', 'pending_signature' => 'Chờ GĐ ký',
            'director_signed' => 'Chờ NV ký',
            'employee_signed', 'signed' => 'Đã ký',
            'active' => 'Có hiệu lực',
            'expired' => 'Hết hạn',
            'rejected' => 'Từ chối',
            'cancelled', 'terminated' => 'Đã chấm dứt',
            'draft' => 'Nháp',
            default => $status ? ucfirst($status) : '—',
        };
    };
@endphp
<div class="page-head">
    <div>
        <h1>Thông tin nhân viên</h1>
    </div>
    <div>
        @if(auth()->user()?->canManageHr() && \App\Support\RequestApprover::hrMayManage(auth()->user(), $employee))
            <a class="btn" href="{{ route('transfers.create', ['employee' => $employee->id]) }}">Điều chuyển phòng ban</a>
            <a class="btn" href="{{ route('employees.edit', $employee) }}">Sửa thông tin</a>
        @endif
        <a class="btn link" href="{{ route('employees.index') }}">Quay lại</a>
    </div>
</div>

<div class="card">
    <div class="grid two-cols">
        <div>
            <div class="field"><label>Mã nhân viên</label><div><code>{{ $employee->employee_code ?? '—' }}</code></div></div>
            <div class="field"><label>Họ và tên</label><div>{{ $employee->name }}</div></div>
            @unless($directorView)
                <div class="field"><label>Email</label><div>{{ $employee->email }}</div></div>
                <div class="field"><label>Giới tính</label><div>{{ match($employee->gender ?? null) { 'male' => 'Nam', 'female' => 'Nữ', 'other' => 'Khác', default => '—' } }}</div></div>
                <div class="field"><label>Ngày sinh</label><div>{{ optional($employee->dob)->format('d/m/Y') ?? '—' }}</div></div>
                <div class="field"><label>CCCD / CMND</label><div>{{ $employee->cccd ?? '—' }}</div></div>
                <div class="field"><label>Số điện thoại</label><div>{{ $employee->phone ?? '—' }}</div></div>
                <div class="field"><label>Địa chỉ</label><div>{{ $employee->address ?? '—' }}</div></div>
            @endunless
        </div>
        <div>
            <div class="field"><label>Phòng ban</label><div>{{ optional($employee->department)->name ?? 'Chưa xác định' }}</div></div>
            <div class="field"><label>Chức vụ</label><div>{{ $employee->position ?? '—' }}</div></div>
            <div class="field"><label>Ngày vào làm</label><div>{{ optional($employee->start_date)->format('d/m/Y') ?? '—' }}</div></div>
            @unless($directorView)
                <div class="field"><label>Trình độ học vấn</label><div>{{ $employee->education ?? '—' }}</div></div>
                <div class="field"><label>Kinh nghiệm</label><div>{{ $employee->experience ?? '—' }}</div></div>
                <div class="field"><label>Số ngày phép còn lại</label><div>{{ $employee->leave_balance ?? 0 }} ngày</div></div>
            @endunless
            <div class="field"><label>Trạng thái</label><div>{{ $employee->statusLabel() }}@if($employee->terminated_at) · ngày nghỉ {{ $employee->terminated_at->format('d/m/Y') }}@endif</div></div>
            @unless($directorView)
                <div class="field"><label>Ngày tạo</label><div>{{ $employee->created_at->format('d/m/Y H:i') }}</div></div>
            @endunless
        </div>
    </div>
</div>

@if($employee->positionHistories->isNotEmpty())
<div class="card" style="margin-top:16px;">
    <h3 style="margin-top:0;">Lịch sử chức vụ / điều chuyển</h3>
    <table>
        <thead>
            <tr>
                <th>Chức vụ</th>
                <th>Phòng ban</th>
                <th>Thời gian</th>
                <th>Trạng thái</th>
                <th>Ghi chú</th>
            </tr>
        </thead>
        <tbody>
            @foreach($employee->positionHistories as $row)
                <tr>
                    <td>{{ $row->position_name }}</td>
                    <td>{{ $row->department_name ?: '—' }}</td>
                    <td>{{ $row->tenureLabel() }}</td>
                    <td>{{ $row->statusLabel() }}</td>
                    <td>{{ $row->note ?: ($row->decision_ref ?: '—') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@if($directorView)
<div class="card" style="margin-top:16px;">
    <h3 style="margin-top:0;">Hợp đồng</h3>
    @if($employee->contracts->isEmpty())
    @else
        <table>
            <thead>
                <tr>
                    <th>Mã HĐ</th>
                    <th>Loại</th>
                    <th>Trạng thái</th>
                    <th>Hiệu lực</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($employee->contracts as $contract)
                    <tr>
                        <td><code>{{ $contract->contract_code ?? '—' }}</code></td>
                        <td>{{ $contract->contract_type ?? '—' }}</td>
                        <td>{{ $contractStatusLabel($contract->status) }}</td>
                        <td>
                            {{ optional($contract->start_date)->format('d/m/Y') ?? '—' }}
                            →
                            {{ optional($contract->end_date)->format('d/m/Y') ?? '—' }}
                        </td>
                        <td><a class="btn link" href="{{ route('contracts.show', $contract) }}">Xem</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endif
@endsection
