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

@if(auth()->user()?->is_hr)
    <div class="alert alert-light border mb-3" style="font-size:14px;">
        <strong>Gửi ký</strong> chỉ xuất hiện với hợp đồng <strong>nháp</strong> (vừa tạo, chưa khóa tài liệu).
        Hợp đồng demo đã <em>active</em> / đã ký sẽ không có nút này — hãy <a href="{{ route('contracts.create') }}">tạo hợp đồng mới</a> để demo luồng ký.
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
                        $alertLevel = $contract->alertLevel();
                        $daysLeft = $contract->daysUntilExpiry();

                        $badge = match($contract->status) {
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
                        $label = $contract->statusLabel();
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
                                                onclick="return confirm('Cập nhật lương hợp đồng [{{ $contract->contract_code }}] theo bảng lương T{{ $latestPayroll->month }}/{{ $latestPayroll->year }}?')"
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
                                          onsubmit="return confirm('Xác nhận ký {{ $contract->contract_code }} phía doanh nghiệp? Đây là mô phỏng, chưa phải chứng thư số pháp lý.');">
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
