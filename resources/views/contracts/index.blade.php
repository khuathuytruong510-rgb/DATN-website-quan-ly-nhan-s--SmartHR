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

                        $badge = match($contract->status) {
                            'waiting_employee_signature', 'waiting_director_signature',
                            'waiting_employee', 'waiting_director' => 'warning',
                            'active'    => 'success',
                            'expiring'  => 'info',
                            'expired'   => 'danger',
                            'rejected'  => 'dark',
                            'cancelled' => 'secondary',
                            default     => 'secondary',
                        };
                        $label = match($contract->status) {
                            'waiting_employee_signature' => 'Chờ NV ký',
                            'waiting_director_signature' => 'Chờ GĐ ký',
                            'waiting_employee'           => 'Chờ NV ký',
                            'waiting_director'           => 'Chờ GĐ ký',
                            'active'    => 'Có hiệu lực',
                            'expiring'  => 'Sắp hết hạn',
                            'expired'   => 'Hết hạn',
                            'rejected'  => 'Từ chối',
                            'cancelled' => 'Đã hủy',
                            default     => 'Chờ xử lý',
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
                        <td><span class="badge bg-{{ $badge }}">{{ $label }}</span></td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('contracts.show', $contract) }}">Xem</a>
                                @if(auth()->user()?->canManageHr())
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('contracts.edit', $contract) }}">Sửa</a>
                                    <a class="btn btn-sm btn-outline-info" href="{{ route('contracts.renew', $contract) }}"
                                       title="{{ in_array($contract->status, ['expired','expiring']) ? 'Gia hạn hợp đồng' : 'Tạo hợp đồng kế tiếp' }}">
                                        🔄 Gia hạn
                                    </a>
                                    @if($hasMismatch)
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
                                @if(optional($contract->employee)->email === auth()->user()?->email && ! $contract->employee_signed_at)
                                    <form action="{{ route('contracts.sign', $contract) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="party" value="employee">
                                        <button class="btn btn-sm btn-outline-success" type="submit">✍️ NV ký</button>
                                    </form>
                                @elseif(auth()->user()?->is_director && $contract->employee_signed_at && ! $contract->director_signed_at)
                                    <form action="{{ route('contracts.sign', $contract) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="party" value="director">
                                        <button class="btn btn-sm btn-outline-success" type="submit">✍️ GĐ ký</button>
                                    </form>
                                @endif
                            </div>
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
