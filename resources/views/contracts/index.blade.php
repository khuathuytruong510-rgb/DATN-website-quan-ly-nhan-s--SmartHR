@extends('layouts.app')

@section('content')
<div class="contract-page">
@include('components.module_header', [
    'title' => 'Hợp đồng',
    'subtitle' => 'Danh sách hợp đồng lao động và trạng thái ký kết.',
    'buttonText' => auth()->user()?->canManageHr() ? 'Tạo hợp đồng' : null,
    'buttonRoute' => auth()->user()?->canManageHr() ? route('contracts.create') : null,
])

@if(session('success'))
    <div class="alert alert-success mb-3">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger mb-3">{{ session('error') }}</div>
@endif

@if(auth()->user()?->canManageHr())
<div class="mb-3 d-flex flex-wrap gap-2">
    <form action="{{ route('contracts.sync_salary_from_contract') }}" method="POST" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-sm btn-primary"
            data-confirm="Đồng bộ toàn bộ mức lương từ hợp đồng đang hiệu lực vào các phiếu lương (chỉ áp dụng cho phiếu chưa vào quy trình duyệt)?">
            🔄 Đồng bộ HĐ → Bảng lương (tất cả)
        </button>
    </form>
    <form action="{{ route('contracts.sync_salary_from_payroll') }}" method="POST" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-sm btn-outline-primary"
            data-confirm="Đồng bộ toàn bộ lương từ bảng lương gần nhất vào các hợp đồng đang lệch?">
            💰 Đồng bộ Bảng lương → HĐ (tất cả)
        </button>
    </form>
</div>
@endif

@php
    $contractTypeOptions = [
        'internship' => 'Thực tập',
        'probation' => 'Thử việc',
        'fixed_term' => 'Xác định thời hạn',
        'indefinite' => 'Không xác định thời hạn',
        'official' => 'Chính thức',
        'seasonal' => 'Thời vụ',
    ];
    $statusOptions = [
        'draft' => 'Nháp',
        'waiting_employee_signature' => 'Chờ nhân viên ký',
        'waiting_director_signature' => 'Chờ giám đốc ký',
        'pending_signature' => 'Chờ ký',
        'director_signed' => 'Giám đốc đã ký',
        'employee_signed' => 'Nhân viên đã ký',
        'signed' => 'Đã ký',
        'active' => 'Có hiệu lực',
        'expiring' => 'Sắp hết hạn',
        'expired' => 'Hết hiệu lực',
    ];
    $filters = $filters ?? [];
@endphp

<form method="GET" action="{{ route('contracts.index') }}" class="card p-3 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-12 col-md-4">
            <label class="form-label" for="contract-search">Tìm hợp đồng</label>
            <input id="contract-search" class="form-control" type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Mã HĐ, tên nhân viên...">
        </div>
        <div class="col-12 col-md-2">
            <label class="form-label" for="contract-status">Trạng thái</label>
            <select id="contract-status" class="form-select" name="status">
                <option value="">Tất cả</option>
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label" for="contract-type">Loại hợp đồng</label>
            <select id="contract-type" class="form-select" name="contract_type">
                <option value="">Tất cả</option>
                @foreach($contractTypeOptions as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['contract_type'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label" for="contract-department">Phòng ban</label>
            <select id="contract-department" class="form-select" name="department_id">
                <option value="">Tất cả</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" @selected((string) ($filters['department_id'] ?? '') === (string) $department->id)>{{ $department->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Tìm kiếm</button>
            <a href="{{ route('contracts.index') }}" class="btn btn-outline-secondary">Xóa lọc</a>
        </div>
    </div>
</form>

<div class="card p-3">
    @if($contracts->count())
        <div style="overflow-x: auto;">
        <table class="table table-hover align-middle" style="min-width: 1100px;">
            <thead>
                <tr>
                    <th>Mã HĐ</th>
                    <th>Nhân viên</th>
                    <th>Loại HĐ</th>
                    <th>Lương CB (HĐ)</th>
                    <th>Phụ cấp (HĐ)</th>
                    <th>Lương (BL gần nhất)</th>
                    <th>Bắt đầu</th>
                    <th>Kết thúc</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @foreach($contracts as $contract)
                    @php
                        $latestPayroll = $contract->employee
                            ? $contract->employee->payrolls()->orderByDesc('year')->orderByDesc('month')->first()
                            : null;
                        $cBase = (float)($contract->base_salary ?? $contract->salary ?? 0);
                        $pBase = $latestPayroll ? (float)($latestPayroll->base_salary ?? 0) : null;
                        $hasMismatch = $pBase !== null && abs($pBase - $cBase) > 0;
                        $alertLevel = $contract->alertLevel();
                        $daysLeft = $contract->daysUntilExpiry();
                        $isExpiring = in_array($alertLevel, ['expiring', 'urgent'], true);

                        $badge = $isExpiring ? 'warning' : match($contract->status) {
                            'waiting_employee_signature', 'waiting_director_signature',
                            'waiting_employee', 'waiting_director', 'pending_signature',
                            'director_signed', 'employee_signed', 'draft' => 'warning',
                            'signed', 'active' => 'success',
                            'expiring'  => 'info',
                            'expired'   => 'danger',
                            'rejected'  => 'dark',
                            'cancelled' => 'secondary',
                            default     => 'secondary',
                        };
                        $label = $isExpiring ? 'Sắp hết hiệu lực' : $contract->statusLabel();
                        $alertBadge = match($alertLevel) {
                            'overdue', 'expired' => 'danger',
                            'urgent' => 'danger',
                            'expiring' => 'warning',
                            default => null,
                        };
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('contracts.show', $contract) }}" style="font-weight:600;">
                                {{ $contract->contract_code ?? '—' }}
                            </a>
                            @if($contract->parent_contract_id)
                                <span title="Hợp đồng gia hạn" style="font-size:11px;color:#7c3aed;margin-left:4px;">🔄</span>
                            @endif
                        </td>
                        <td>{{ optional($contract->employee)->name ?? '—' }}</td>
                        <td>
                            @php $typeMap = ['internship'=>'Thực tập','probation'=>'Thử việc','fixed_term'=>'Xác định TH','indefinite'=>'Không XĐ TH','official'=>'Chính thức','seasonal'=>'Thời vụ']; @endphp
                            {{ $typeMap[$contract->contract_type] ?? ucfirst(str_replace('_',' ',$contract->contract_type ?? '—')) }}
                        </td>
                        <td>{{ number_format($cBase, 0, ',', '.') }}₫</td>
                        <td>{{ number_format($contract->allowance ?? 0, 0, ',', '.') }}₫</td>
                        <td>
                            @if($pBase !== null)
                                <span style="color:{{ $hasMismatch ? '#d97706' : '#16a34a' }}; font-weight:600;">
                                    {{ number_format($pBase, 0, ',', '.') }}₫
                                </span>
                                @if($hasMismatch)
                                    <span title="Lương bảng lương khác hợp đồng" style="font-size:12px;"> ⚠️</span>
                                @endif
                                <div style="font-size:11px;color:#94a3b8;">T{{ $latestPayroll->month }}/{{ $latestPayroll->year }}</div>
                            @else
                                <span style="color:#94a3b8;font-size:12px;">Chưa có BL</span>
                            @endif
                        </td>
                        <td>{{ optional($contract->start_date)->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ optional($contract->end_date)->format('d/m/Y') ?? 'Không XĐ' }}</td>
                        <td>
                            <span class="badge bg-{{ $badge }}">{{ $label }}</span>
                            @if($alertBadge && $contract->isFullySigned())
                                <div style="margin-top:6px;">
                                    <span class="badge bg-{{ $alertBadge }}">
                                        @if($alertLevel === 'expiring') ⚠️ Còn {{ $daysLeft }} ngày
                                        @elseif($alertLevel === 'urgent') 🔴 Còn {{ $daysLeft }} ngày
                                        @elseif($alertLevel === 'expired') 🔴 Đã hết hạn
                                        @else 🚨 Quá hạn {{ abs((int) $daysLeft) }} ngày
                                        @endif
                                    </span>
                                </div>
                            @endif
                            @if($contract->latestExpiryAction)
                                <div style="font-size:11px;color:#64748b;margin-top:4px;">{{ $contract->latestExpiryAction->label() }}</div>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('contracts.show', $contract) }}">Xem</a>
                                @if(auth()->user()?->canManageHr() && $contract->needsExpiryHandling())
                                    <button type="button" class="btn btn-sm btn-warning" onclick="document.getElementById('handle-contract-{{ $contract->id }}').showModal()">Xử lý</button>
                                @endif
                                @if(auth()->user()?->canManageHr())
                                    @unless($contract->isContentLocked())
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('contracts.edit', $contract) }}">Sửa</a>
                                    @endunless
                                    <a class="btn btn-sm btn-outline-info" href="{{ route('contracts.renew', $contract) }}"
                                       title="Gia hạn thời hạn, giữ nguyên nội dung hợp đồng">
                                        🔄 Gia hạn
                                    </a>
                                    @if($hasMismatch && ! $contract->isContentLocked())
                                        <form action="{{ route('contracts.sync_salary', $contract) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-warning"
                                                data-confirm="Cập nhật lương hợp đồng [{{ $contract->contract_code }}] theo bảng lương T{{ $latestPayroll->month }}/{{ $latestPayroll->year }}?"
                                                title="Đồng bộ lương từ bảng lương">
                                                💰 Đồng bộ lương
                                            </button>
                                        </form>
                                    @endif
                                @endif
                                @if(auth()->user()?->is_hr && $contract->isAwaitingHrSend())
                                    <form action="{{ route('contracts.send_for_signature', $contract) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-warning" type="submit">Gửi ký</button>
                                    </form>
                                @endif
                                @if(auth()->user()?->is_director && $contract->isPendingDirectorEsign())
                                    <form action="{{ route('contracts.sign', $contract) }}" method="POST" class="d-inline"
                                        data-confirm="Xác nhận ký {{ $contract->contract_code }} phía doanh nghiệp? Đây là mô phỏng, chưa phải chứng thư số pháp lý.">
                                        @csrf
                                        <input type="hidden" name="party" value="director">
                                        <button class="btn btn-sm btn-outline-success" type="submit">Ký (GĐ)</button>
                                    </form>
                                @endif
                            </div>
                            @if(auth()->user()?->canManageHr() && $contract->needsExpiryHandling())
                                <x-contract_handle_dialog :contract="$contract" />
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>

        <div class="pagination mt-2">{{ $contracts->links() }}</div>
    @else
        <div class="empty">Chưa có hợp đồng nào.</div>
    @endif
</div>
</div>
@endsection
