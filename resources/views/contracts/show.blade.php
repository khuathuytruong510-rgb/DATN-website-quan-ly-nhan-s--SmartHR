@extends('layouts.app')

@section('content')
<div class="contract-page">
<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Chi tiết hợp đồng</h2>
            <p class="text-muted mb-0">Thông tin đầy đủ về hợp đồng, người ký và lịch sử gia hạn.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('contracts.index') }}">Quay lại</a>
            @if(auth()->user()?->is_admin || auth()->user()?->is_hr)
                <a class="btn btn-outline-secondary" href="{{ route('contracts.edit', $contract) }}">Sửa</a>
                <a class="btn btn-outline-info" href="{{ route('contracts.renew', $contract) }}">Gia hạn</a>
            @endif
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Thông tin hợp đồng</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><strong>Mã hợp đồng:</strong> {{ $contract->contract_code ?? '—' }}</div>
                        <div class="col-md-6"><strong>Loại hợp đồng:</strong> {{ $contract->contract_type ? ucfirst(str_replace('_', ' ', $contract->contract_type)) : '—' }}</div>
                        <div class="col-md-6"><strong>Ngày bắt đầu:</strong> {{ optional($contract->start_date)->format('d/m/Y') ?? '—' }}</div>
                        <div class="col-md-6"><strong>Ngày kết thúc:</strong> {{ optional($contract->end_date)->format('d/m/Y') ?? '—' }}</div>
                        <div class="col-md-6"><strong>Lương:</strong> {{ number_format($contract->salary ?? 0, 0, ',', '.') }} VNĐ</div>
                        <div class="col-md-6"><strong>Phụ cấp:</strong> {{ number_format($contract->allowance ?? 0, 0, ',', '.') }} VNĐ</div>
                        <div class="col-md-6"><strong>Nơi làm việc:</strong> {{ $contract->workplace ?? '—' }}</div>
                        <div class="col-md-6"><strong>Ca làm việc:</strong> {{ $contract->working_schedule === 'morning' ? 'Sáng' : ($contract->working_schedule === 'evening' ? 'Tối' : ($contract->working_schedule === 'morning_evening' ? 'Sáng và tối' : '—')) }}</div>
                        <div class="col-md-6"><strong>Người tạo:</strong> {{ optional($contract->createdByUser)->name ?? '—' }}</div>
                        <div class="col-md-12"><strong>Ghi chú:</strong> {{ $contract->notes ?? '—' }}</div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Quyền lợi cố định</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center">
                                <div class="text-muted small mb-1">Nghỉ phép không lương</div>
                                <div class="fs-4 fw-bold text-primary">{{ $contract->allowed_unpaid_leave_days_per_month ?? 1 }} ngày/tháng</div>
                                <div class="text-muted small">Khi xin nghỉ phép sẽ không được hưởng lương</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center">
                                <div class="text-muted small mb-1">Điểm danh bù</div>
                                <div class="fs-4 fw-bold text-success">{{ $contract->allowed_makeup_attendance_per_month ?? 3 }} lần/tháng</div>
                                <div class="text-muted small">Được phép bù công khi bỏ lỡ</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3 text-center">
                                <div class="text-muted small mb-1">Nghỉ thai sản</div>
                                <div class="fs-4 fw-bold text-info">{{ $contract->allowed_maternity_leave_days ?? 180 }} ngày</div>
                                <div class="text-muted small">Theo Luật Lao động Việt Nam</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Thông tin nhân viên</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><strong>Nhân viên:</strong> {{ optional($contract->employee)->name ?? '—' }}</div>
                        <div class="col-md-6"><strong>Email:</strong> {{ optional($contract->employee)->email ?? '—' }}</div>
                        <div class="col-md-6"><strong>Chức vụ:</strong> {{ optional($contract->employee)->position ?? '—' }}</div>
                        <div class="col-md-6"><strong>Phòng ban:</strong> {{ optional(optional($contract->employee)->department)->name ?? '—' }}</div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Thông tin ký kết</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><strong>Nhân viên ký:</strong> {{ $contract->employee_signed_at ? optional($contract->employee_signed_at)->format('d/m/Y H:i') : 'Chưa ký' }}</div>
                        <div class="col-md-6"><strong>Giám đốc ký:</strong> {{ $contract->director_signed_at ? optional($contract->director_signed_at)->format('d/m/Y H:i') : 'Chưa ký' }}</div>
                        @if($contract->document_path)
                            <div class="col-md-12"><strong>File hợp đồng:</strong> <a href="{{ Storage::url($contract->document_path) }}" target="_blank">{{ $contract->document_name ?? 'Tải file' }}</a></div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Điều khoản</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3"><strong>Điều khoản mặc định:</strong></div>
                    <div class="border rounded p-3 bg-light">{{ $contract->contract_content ?? $contract->terms ?? '—' }}</div>
                    @if($contract->additional_terms)
                        <div class="mt-3"><strong>Điều khoản bổ sung:</strong></div>
                        <div class="border rounded p-3 bg-light">{{ $contract->additional_terms }}</div>
                    @endif
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Lịch sử gia hạn</h5>
                </div>
                <div class="card-body">
                    @if($contract->parent_contract_id || $contract->renewals->count())
                        <ul class="mb-0">
                            @if($contract->parent_contract_id)
                                <li>Hợp đồng này là bản gia hạn của <a href="{{ route('contracts.show', $contract->parentContract) }}">{{ $contract->parentContract?->contract_code ?? '#' . $contract->parent_contract_id }}</a>.</li>
                            @endif
                            @foreach($contract->renewals as $renewal)
                                <li><a href="{{ route('contracts.show', $renewal) }}">{{ $renewal->contract_code }}</a> — {{ optional($renewal->start_date)->format('d/m/Y') }} đến {{ optional($renewal->end_date)->format('d/m/Y') }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mb-0">Chưa có lịch sử gia hạn.</p>
                    @endif
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Nhật ký hợp đồng</h5>
                </div>
                <div class="card-body">
                    @if($contract->logs->count())
                        <ul class="timeline list-unstyled mb-0">
                            @foreach($contract->logs->sortByDesc('created_at') as $log)
                                <li class="border-start ps-3 pb-3">
                                    <div class="fw-semibold">{{ $log->message }}</div>
                                    <div class="text-muted small">{{ optional($log->created_at)->format('d/m/Y H:i') }}</div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mb-0">Chưa có nhật ký.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">

            {{-- Trạng thái --}}
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white"><h5 class="mb-0">Trạng thái</h5></div>
                <div class="card-body">
                    @php
                        $badge = match($contract->status) {
                            'waiting_employee_signature','waiting_director_signature',
                            'waiting_employee','waiting_director' => 'warning',
                            'active'    => 'success',
                            'expiring'  => 'info',
                            'expired'   => 'danger',
                            'rejected'  => 'dark',
                            'cancelled' => 'secondary',
                            default     => 'secondary',
                        };
                        $label = match($contract->status) {
                            'waiting_employee_signature' => 'Chờ nhân viên ký',
                            'waiting_director_signature' => 'Chờ giám đốc ký',
                            'waiting_employee'           => 'Chờ nhân viên ký',
                            'waiting_director'           => 'Chờ giám đốc ký',
                            'active'    => 'Có hiệu lực',
                            'expiring'  => 'Sắp hết hạn',
                            'expired'   => 'Hết hạn',
                            'rejected'  => 'Từ chối',
                            'cancelled' => 'Đã hủy',
                            default     => 'Chờ xử lý',
                        };
                    @endphp
                    <div class="mb-3"><span class="badge bg-{{ $badge }}">{{ $label }}</span></div>
                    <div class="mb-3">
                        <strong>Ngày còn lại:</strong>
                        @if($daysRemaining === null) —
                        @elseif($daysRemaining < 0) <span class="text-danger">Đã hết hạn {{ abs($daysRemaining) }} ngày trước</span>
                        @elseif($daysRemaining <= 30) <span class="text-warning fw-bold">{{ $daysRemaining }} ngày ⚠️</span>
                        @else {{ $daysRemaining }} ngày
                        @endif
                    </div>

                    @if(auth()->user()?->is_admin || auth()->user()?->is_hr || optional($contract->employee)->email === auth()->user()?->email)
                    <form action="{{ route('contracts.sign', $contract) }}" method="POST" class="mb-2">
                        @csrf
                        <input type="hidden" name="party" value="{{ optional($contract->employee)->email === auth()->user()?->email ? 'employee' : 'director' }}">
                        <button class="btn btn-primary w-100" type="submit">✍️ Ký hợp đồng</button>
                    </form>
                    @endif

                    @if(in_array($contract->status, ['active','expiring','expired']) && (auth()->user()?->is_admin || auth()->user()?->is_hr))
                    <a href="{{ route('contracts.renew', $contract) }}" class="btn btn-outline-success w-100">🔄 Gia hạn hợp đồng</a>
                    @endif
                </div>
            </div>

            {{-- So sánh lương hợp đồng vs bảng lương gần nhất --}}
            @if($payroll)
            @php
                $cBase  = (float)($contract->base_salary ?? $contract->salary ?? 0);
                $pBase  = (float)($payroll->base_salary ?? 0);
                $lDiff  = $pBase - $cBase;
                $hasDif = abs($lDiff) > 0;
                $borderColor = $hasDif ? '#fde68a' : '#86efac';
            @endphp
            <div class="card shadow-sm" style="border:1.5px solid {{ $borderColor }};">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0" style="font-size:15px;">💰 So sánh lương</h5>
                    @if($hasDif)
                        <span class="badge bg-warning text-dark">⚠ Chênh lệch</span>
                    @else
                        <span class="badge bg-success">✓ Khớp</span>
                    @endif
                </div>
                <div class="card-body">
                    @if($hasDif)
                    <div style="background:#fffbeb;border-radius:8px;padding:10px 12px;margin-bottom:12px;font-size:13px;color:#92400e;">
                        Bảng lương tháng <strong>{{ $payroll->month }}/{{ $payroll->year }}</strong>
                        có mức lương khác hợp đồng hiện tại.
                    </div>
                    @endif

                    <table class="table table-sm mb-3">
                        <thead>
                            <tr style="font-size:12px;">
                                <th></th>
                                <th>Hợp đồng</th>
                                <th>Bảng lương</th>
                            </tr>
                        </thead>
                        <tbody style="font-size:13px;">
                            <tr>
                                <td style="color:#64748b;">Lương CB</td>
                                <td><strong>{{ number_format($cBase,0,',','.') }}₫</strong></td>
                                <td>
                                    <strong style="color:{{ $lDiff>0?'#16a34a':($lDiff<0?'#dc2626':'inherit') }};">
                                        {{ number_format($pBase,0,',','.') }}₫
                                        @if($hasDif)
                                        <span style="font-size:11px;font-weight:400;">
                                            ({{ $lDiff>0?'+':'' }}{{ number_format($lDiff,0,',','.') }})
                                        </span>
                                        @endif
                                    </strong>
                                </td>
                            </tr>
                            <tr>
                                <td style="color:#64748b;">Phụ cấp</td>
                                <td>{{ number_format($contract->allowance??0,0,',','.') }}₫</td>
                                <td>{{ number_format($payroll->allowance??0,0,',','.') }}₫</td>
                            </tr>
                            <tr>
                                <td style="color:#64748b;">Tháng</td>
                                <td colspan="2" style="color:#475569;">{{ $payroll->month }}/{{ $payroll->year }}</td>
                            </tr>
                        </tbody>
                    </table>

                    @if($hasDif && (auth()->user()?->is_admin || auth()->user()?->is_hr))
                    <form method="POST" action="{{ route('contracts.sync_salary', $contract) }}" class="mb-2">
                        @csrf
                        <button type="submit" class="btn btn-warning w-100"
                            onclick="return confirm('Cập nhật lương hợp đồng theo bảng lương tháng {{ $payroll->month }}/{{ $payroll->year }}?')">
                            🔄 Đồng bộ lương hợp đồng
                        </button>
                    </form>
                    @endif

                    <a href="{{ route('payroll.show', $payroll) }}" class="btn btn-outline-secondary w-100" style="font-size:13px;">
                        Xem bảng lương tháng {{ $payroll->month }}/{{ $payroll->year }}
                    </a>
                </div>
            </div>
            @else
            <div class="card shadow-sm">
                <div class="card-body text-center" style="color:#94a3b8;padding:20px;">
                    <div style="font-size:32px;margin-bottom:8px;">📄</div>
                    <p style="margin:0;font-size:13px;">Nhân viên chưa có bảng lương nào.</p>
                </div>
            </div>
            @endif

        </div>
    </div>
</div>
</div>
@endsection
