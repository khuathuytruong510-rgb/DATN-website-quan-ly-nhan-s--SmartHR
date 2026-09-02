@extends('layouts.app')

@section('content')

<div class="container-fluid">

    {{-- Header --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h3 class="fw-bold mb-1">
                        @if(!empty($paymentFocus))
                            Thanh toán lương
                        @elseif(!empty($hrWorkOnly))
                            Kiểm tra dữ liệu công
                        @else
                            Bảng lương nhân viên
                        @endif
                    </h3>
                    <p class="text-muted mb-0">
                        @if(!empty($paymentFocus))
                            Chỉ phiếu nhân viên đã xác nhận. Kế toán thanh toán → salary_payment → Đã trả. Không thanh toán phiếu chưa xác nhận hoặc đã trả.
                        @elseif(!empty($hrWorkOnly))
                            Hệ thống chốt kỳ sau ngày cuối tháng → HR kiểm tra nguồn → gửi kế toán tính. Kỳ lương từ {{ $periodMeta['start_label'] ?? '01/'.sprintf('%02d/%d', $month, $year) }} đến {{ $periodMeta['end_label'] ?? '' }}.
                            {{ $periodMeta['formula_label'] ?? '' }}
                        @else
                            Hệ thống chốt kỳ → HR kiểm tra nguồn → Kế toán tính → HR xác nhận phiếu → Giám đốc duyệt → NV xác nhận → Thanh toán.
                        @endif
                    </p>
                </div>

                @php
                    $user = auth()->user();
                    $canGenerate = $user->is_accountant && empty($paymentFocus);
                    $pendingHrCount = $payrolls->whereIn('status', \App\Services\PayrollPaymentWorkflowService::calculatedStatuses())->count();
                    $pendingDirectorCount = $payrolls->whereIn('status', \App\Services\PayrollPaymentWorkflowService::hrCheckedStatuses())->count();
                    $canBulkHrReview = $user->is_hr;
                    $canBulkFinalApprove = $user->is_director;
                    $periodLocked = (bool) optional($periodLock ?? null)->is_locked;
                    $periodVerified = (bool) optional($periodLock ?? null)->hr_verified_at;
                    $unlockPending = optional($periodLock ?? null)->unlock_request_status === 'pending';
                    $periodReady = $periodLocked && $periodVerified && ! $unlockPending;
                    $periodMeta = $periodMeta ?? app(\App\Services\PayrollCalculationService::class)->periodMeta((int) $month, (int) $year);
                    $hrWorkOnly = !empty($hrWorkOnly);
                    $contractTypeLabel = function (?string $type): string {
                        return match ($type) {
                            'internship' => 'Thực tập',
                            'probation' => 'Thử việc',
                            'fixed_term' => 'Xác định thời hạn',
                            'indefinite' => 'Không xác định thời hạn',
                            'official' => 'Chính thức',
                            'seasonal' => 'Thời vụ',
                            default => $type ? ucfirst(str_replace('_', ' ', $type)) : '—',
                        };
                    };
                @endphp

                <div class="d-flex flex-column gap-2 align-items-stretch" style="min-width:min(520px,100%);">
                    <form method="GET" action="{{ route('payroll.index') }}">
                        <div class="row g-2">
                            <div class="col-md-3">
                                <label>Tháng</label>
                                <select name="month" class="form-select">
                                    @for($i=1;$i<=12;$i++)
                                        <option value="{{ $i }}" {{ $month==$i?'selected':'' }}>
                                            Tháng {{ $i }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>Năm</label>
                                <select name="year" class="form-select">
                                    @for($y=2025;$y<=2035;$y++)
                                        <option value="{{ $y }}" {{ $year==$y?'selected':'' }}>
                                            {{ $y }}
                                        </option>
                                    @endfor
                                </select>
                            </div>
                            <div class="col-md-6 d-flex align-items-end gap-2 flex-wrap">
                                <button class="btn btn-outline-primary" type="submit">Xem kỳ lương</button>
                                @if($canGenerate)
                                <a class="btn btn-primary" href="{{ route('accountant.payroll.generate') }}">
                                    <i class="bi bi-calculator"></i>
                                    Sang tính lương
                                </a>
                                @endif
                                @unless($hrWorkOnly)
                                <a class="btn btn-outline-secondary"
                                   href="{{ route('payroll.print', ['month' => $month, 'year' => $year]) }}"
                                   target="_blank">
                                    <i class="bi bi-printer"></i>
                                    In bảng lương
                                </a>
                                @endunless
                            </div>
                        </div>
                    </form>
                    <p class="text-muted small mb-3 mt-2">
                        Kỳ lương từ <strong>{{ $periodMeta['start_label'] }}</strong> đến <strong>{{ $periodMeta['end_label'] }}</strong>.
                        {{ $periodMeta['formula_label'] }}.
                    </p>

                    @php
                        $periodVerified = (bool) optional($periodLock ?? null)->hr_verified_at;
                        $unlockPending = optional($periodLock ?? null)->unlock_request_status === 'pending';
                        $periodReady = $periodLocked && $periodVerified && ! $unlockPending;
                    @endphp

                    @if($user->is_hr)
                        @if($unlockPending)
                            <div class="alert alert-warning border mb-0 py-2 px-3">
                                Đã gửi yêu cầu mở khóa kỳ {{ sprintf('%02d/%d', $month, $year) }} — chờ Giám đốc duyệt.
                                <div class="small mt-1">{{ optional($periodLock)->unlock_request_reason }}</div>
                            </div>
                        @elseif($periodLocked && $periodVerified)
                            <form method="POST" action="{{ route('payroll.period.unlock') }}"
                                  onsubmit="return confirm('Gửi yêu cầu mở khóa kỳ {{ sprintf('%02d/%d', $month, $year) }} cho Giám đốc duyệt?');">
                                @csrf
                                <input type="hidden" name="month" value="{{ $month }}">
                                <input type="hidden" name="year" value="{{ $year }}">
                                <div class="d-flex gap-2 flex-wrap align-items-end">
                                    <div style="flex:1;min-width:220px;">
                                        <label class="form-label mb-1">Kỳ đã chốt &amp; HR đã xác nhận — Kế toán có thể tính</label>
                                        <input type="text" name="unlock_reason" class="form-control" required minlength="10" maxlength="500"
                                               placeholder="Lý do cần mở khóa (gửi Giám đốc duyệt)">
                                    </div>
                                    <button type="submit" class="btn btn-outline-danger">Gửi yêu cầu mở khóa</button>
                                </div>
                            </form>
                        @elseif($periodLocked)
                            <div class="d-flex gap-2 flex-wrap align-items-end">
                                <form method="POST" action="{{ route('payroll.period.verify') }}"
                                      onsubmit="return confirm('Xác nhận đã kiểm tra chấm công / nghỉ phép / OT kỳ {{ sprintf('%02d/%d', $month, $year) }}?\nSau đó Kế toán được tính lương.');">
                                    @csrf
                                    <input type="hidden" name="month" value="{{ $month }}">
                                    <input type="hidden" name="year" value="{{ $year }}">
                                    <button type="submit" class="btn btn-primary">Đã kiểm tra nguồn — gửi kế toán tính</button>
                                </form>
                                <form method="POST" action="{{ route('payroll.period.unlock') }}" class="d-flex gap-2 flex-wrap align-items-end flex-grow-1"
                                      onsubmit="return confirm('Gửi yêu cầu mở khóa cho Giám đốc? Kỳ vẫn khóa đến khi được duyệt.');">
                                    @csrf
                                    <input type="hidden" name="month" value="{{ $month }}">
                                    <input type="hidden" name="year" value="{{ $year }}">
                                    <div style="flex:1;min-width:200px;">
                                        <input type="text" name="unlock_reason" class="form-control" required minlength="10" maxlength="500"
                                               placeholder="Có sai sót? Nhập lý do mở khóa (≥10 ký tự)">
                                    </div>
                                    <button type="submit" class="btn btn-outline-danger">Yêu cầu mở khóa</button>
                                </form>
                            </div>
                        @else
                            <div class="d-flex gap-2 flex-wrap align-items-center">
                                <div class="alert alert-light border mb-0 py-2 px-3 flex-grow-1">
                                    Kỳ {{ sprintf('%02d/%d', $month, $year) }} đang mở — có thể sửa chấm công / nghỉ phép.
                                    @if(optional($periodLock)->unlock_reason)
                                        <div class="small mt-1">Đã mở khóa: {{ $periodLock->unlock_reason }}</div>
                                    @else
                                        <div class="small mt-1">Hệ thống tự chốt sau ngày cuối tháng.</div>
                                    @endif
                                </div>
                                <form method="POST" action="{{ route('payroll.period.lock') }}"
                                      onsubmit="return confirm('Khóa lại kỳ {{ sprintf('%02d/%d', $month, $year) }} sau khi chỉnh xong?');">
                                    @csrf
                                    <input type="hidden" name="month" value="{{ $month }}">
                                    <input type="hidden" name="year" value="{{ $year }}">
                                    <button type="submit" class="btn btn-outline-primary">Khóa lại kỳ</button>
                                </form>
                            </div>
                        @endif
                    @elseif($user->is_director && $unlockPending)
                        <div class="card border-warning mb-0 p-3">
                            <strong>Yêu cầu mở khóa kỳ {{ sprintf('%02d/%d', $month, $year) }}</strong>
                            <p class="mb-2 mt-1">{{ optional($periodLock)->unlock_request_reason }}</p>
                            <p class="small text-muted mb-2">
                                HR: {{ optional(optional($periodLock)->unlockRequester)->name ?? '—' }}
                                · {{ optional(optional($periodLock)->unlock_requested_at)->format('d/m/Y H:i') }}
                            </p>
                            <div class="d-flex gap-2 flex-wrap">
                                <form method="POST" action="{{ route('payroll.period.unlock.approve') }}"
                                      onsubmit="return confirm('Duyệt mở khóa kỳ {{ sprintf('%02d/%d', $month, $year) }}?');">
                                    @csrf
                                    <input type="hidden" name="month" value="{{ $month }}">
                                    <input type="hidden" name="year" value="{{ $year }}">
                                    <button type="submit" class="btn btn-success">Duyệt mở khóa</button>
                                </form>
                                <form method="POST" action="{{ route('payroll.period.unlock.reject') }}" class="d-flex gap-2 flex-wrap"
                                      onsubmit="return confirm('Từ chối mở khóa?');">
                                    @csrf
                                    <input type="hidden" name="month" value="{{ $month }}">
                                    <input type="hidden" name="year" value="{{ $year }}">
                                    <input type="text" name="note" class="form-control" placeholder="Ghi chú từ chối (tuỳ chọn)" style="min-width:200px;">
                                    <button type="submit" class="btn btn-outline-danger">Từ chối</button>
                                </form>
                            </div>
                        </div>
                    @elseif($canGenerate && ! $periodReady)
                        <div class="alert alert-light border mb-0 py-2 px-3">
                            @if(! $periodLocked)
                                Kỳ chưa chốt — chờ hệ thống chốt sau ngày cuối tháng.
                            @elseif($unlockPending)
                                Đang chờ Giám đốc duyệt mở khóa.
                            @else
                                Kỳ đã chốt — chờ HR xác nhận kiểm tra nguồn trước khi tính lương.
                            @endif
                        </div>
                    @endif

                    @if($canBulkHrReview && $periodVerified && ! $unlockPending && $pendingHrCount > 0)
                        <form method="POST" action="{{ route('payroll.review_all') }}"
                              onsubmit="return confirm('Xác nhận đã kiểm tra dữ liệu nhân sự trên {{ $pendingHrCount }} phiếu lương tháng {{ sprintf('%02d/%d', $month, $year) }}? Sau bước này Giám đốc sẽ phê duyệt cuối.');">
                            @csrf
                            <input type="hidden" name="month" value="{{ $month }}">
                            <input type="hidden" name="year" value="{{ $year }}">
                            <button type="submit" class="btn btn-success"
                                    title="HR kiểm tra dữ liệu tất cả phiếu kế toán đã tính">
                                <i class="bi bi-check2-all"></i>
                                Kiểm tra phiếu đã tính
                                ({{ $pendingHrCount }})
                            </button>
                        </form>
                    @endif

                    @if($canBulkFinalApprove)
                        <form method="POST" action="{{ route('payroll.approve_all') }}"
                              onsubmit="return confirm('Bạn đang phê duyệt {{ $pendingDirectorCount }} phiếu lương của tháng {{ sprintf('%02d/%d', $month, $year) }}. Sau khi phê duyệt, các phiếu sẽ chuyển sang chờ nhân viên xác nhận.\n\nBạn có chắc chắn tiếp tục?');">
                            @csrf
                            <input type="hidden" name="month" value="{{ $month }}">
                            <input type="hidden" name="year" value="{{ $year }}">
                            <button type="submit" class="btn btn-success"
                                    @disabled($pendingDirectorCount < 1)
                                    title="{{ $pendingDirectorCount < 1 ? 'Không có phiếu chờ phê duyệt cuối' : 'Giám đốc phê duyệt cuối các phiếu HR đã kiểm tra' }}">
                                <i class="bi bi-check2-all"></i>
                                Phê duyệt cuối
                                @if($pendingDirectorCount > 0)
                                    ({{ $pendingDirectorCount }})
                                @endif
                            </button>
                        </form>
                    @endif
                </div>

            </div>
        </div>
    </div>

    {{-- Bảng lương --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle payroll-table mb-0">
                    <thead>
                        <tr>
                            <th>Nhân viên</th>
                            <th>Chức vụ</th>
                            @if($hrWorkOnly)
                            <th>Hợp đồng</th>
                            @endif
                            <th class="text-center">Tháng</th>
                            <th class="text-end">{{ $hrWorkOnly ? 'Lương CB (HĐ)' : 'Lương CB' }}</th>
                            @if($hrWorkOnly)
                            <th class="text-end">Phụ cấp (HĐ)</th>
                            @endif
                            <th class="text-center">Ngày công</th>
                            @if($hrWorkOnly)
                            <th class="text-center">Giờ làm</th>
                            @endif
                            <th class="text-center">TC ngày</th>
                            <th class="text-center">Nghỉ phép (Có/Không)</th>
                            <th class="text-center">Lễ hưởng lương</th>
                            <th class="text-center">TC giờ</th>
                            @if($hrWorkOnly)
                            <th class="text-center">Đi muộn</th>
                            <th class="text-center">Vắng</th>
                            @endif
                            @unless($hrWorkOnly)
                            <th class="text-end">Lương đi làm</th>
                            <th class="text-end">Lương nghỉ phép</th>
                            <th class="text-end">Lương lễ</th>
                            <th class="text-end">TC Ngày</th>
                            <th class="text-end">TC Giờ</th>
                            <th class="text-end">Tổng TC</th>
                            <th class="text-end">Phụ cấp</th>
                            <th class="text-end">Thưởng</th>
                            <th class="text-end">BH</th>
                            <th class="text-end">Thuế</th>
                            <th class="text-end">Phạt đi muộn</th>
                            <th class="text-end">Thực nhận</th>
                            @endunless
                            <th class="text-center">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tableRows as $row)
                        @php
                            $payroll = $row instanceof \App\Models\Payroll ? $row : $row->payroll;
                            $employee = $row->employee ?? $payroll?->employee;
                            $requiredDays = $row->required_working_days ?? 26;
                            $calendarDays = $row->calendar_working_days ?? $requiredDays;
                            $dailySalary = $hrWorkOnly ? 0 : ($row->daily_salary ?? ($row->base_salary / 26));
                            $actualDays = $row->working_days ?? 0;
                            $actualWorkingSalary = $hrWorkOnly ? 0 : ($actualDays * $dailySalary);
                            $paidLeaveDays = $row->paid_leave_days ?? 0;
                            $paidLeaveSalary = $hrWorkOnly ? 0 : ($paidLeaveDays * $dailySalary);
                            $paidHolidayDays = $row->paid_holiday_days ?? 0;
                            $paidHolidaySalary = $hrWorkOnly ? 0 : ($row->paid_holiday_salary ?? ($paidHolidayDays * $dailySalary));
                            $rowMonth = $row instanceof \App\Models\Payroll
                                ? (int) ($row->getAttributes()['month'] ?? $row->month)
                                : (int) $row->month;
                            $rowYear = $row instanceof \App\Models\Payroll
                                ? (int) ($row->getAttributes()['year'] ?? $row->year)
                                : (int) $row->year;
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">
                                    {{ $employee->name ?? '—' }}
                                </div>
                            </td>

                            <td>
                               @switch($employee->position ?? '')
                                    @case('Giám Đốc')
                                        <span class="badge text-bg-dark">Giám đốc</span>
                                        @break
                                    @case('Trưởng Phòng Nhân Sự')
                                        <span class="badge text-bg-primary">Trưởng phòng</span>
                                        @break
                                    @default
                                        <span class="badge text-bg-light border text-dark">{{ $employee->position ?? 'Nhân viên' }}</span>
                                @endswitch
                            </td>

                            @if($hrWorkOnly)
                            <td>
                                <div>{{ $contractTypeLabel($row->contract_type ?? null) }}</div>
                                @if(!empty($row->contract_code))
                                @endif
                                @if(!empty($row->working_schedule))
                                @endif
                            </td>
                            @endif

                            <td class="text-center">
                                {{ sprintf('%02d/%d', $rowMonth, $rowYear) }}
                            </td>

                            <td class="text-end">
                                {{ number_format($row->base_salary ?? 0) }}
                            </td>
                            @if($hrWorkOnly)
                            <td class="text-end">
                                {{ number_format($row->allowance ?? 0) }}
                            </td>
                            @endif

                            {{-- Cột Ngày công đi làm --}}
                            <td class="text-center">
                                @if((($row->working_days ?? 0) + $paidLeaveDays) >= $calendarDays)
                                    <span class="text-success fw-semibold">
                                        {{ $actualDays }}/{{ $calendarDays }}
                                    </span>
                                @else
                                    <span class="text-danger fw-semibold">
                                        {{ $actualDays }}/{{ $calendarDays }}
                                    </span>
                                @endif
                            </td>
                            @if($hrWorkOnly)
                            <td class="text-center">{{ number_format($row->work_hours ?? 0, 2) }}</td>
                            @endif
                            <td class="text-center">
                                @if(($row->overtime_days ?? 0) > 0)
                                    <span class="text-primary fw-semibold">+{{ $row->overtime_days }}</span>
                                @else
                                    0
                                @endif
                            </td>

                            {{-- Cột Nghỉ phép + Số tiền bên dưới --}}
                            <td class="text-center">
                                <span class="badge text-bg-info text-dark" title="Phép năm / Không lương">
                                    {{ $paidLeaveDays }} / {{ $row->unpaid_leave_days ?? 0 }} ngày
                                </span>
                                @if($paidLeaveSalary > 0)
                                    <small class="text-success fw-semibold d-block mt-1">
                                        +{{ number_format($paidLeaveSalary) }}
                                    </small>
                                @endif
                            </td>

                            <td class="text-center">
                                @if($paidHolidayDays > 0)
                                    <span class="text-success fw-semibold">{{ $paidHolidayDays }} ngày</span>
                                    @if($paidHolidaySalary > 0)
                                        <small class="text-success fw-semibold d-block mt-1">
                                            +{{ number_format($paidHolidaySalary) }}
                                        </small>
                                    @endif
                                @else
                                    0
                                @endif
                            </td>

                            <td class="text-center">
                                {{ number_format($row->overtime_hours ?? 0, 2) }}
                            </td>

                            @if($hrWorkOnly)
                            <td class="text-center">
                                @if(($row->late_days ?? 0) > 0)
                                    {{ $row->late_days }} ngày / {{ $row->late_minutes }} phút
                                @else
                                    0
                                @endif
                            </td>
                            <td class="text-center">{{ $row->absent_days ?? 0 }}</td>
                            @endif

                            @unless($hrWorkOnly)
                            {{-- TÁCH RIÊNG: Lương ngày công đi làm thực tế --}}
                            <td class="text-end fw-semibold">
                                {{ number_format($actualWorkingSalary) }}
                            </td>

                            {{-- TÁCH RIÊNG: Lương ngày nghỉ phép có hưởng lương --}}
                            <td class="text-end">
                                {{ number_format($paidLeaveSalary) }}
                            </td>

                            <td class="text-end">
                                @if($paidHolidaySalary > 0)
                                    <span class="text-success fw-semibold">{{ number_format($paidHolidaySalary) }}</span>
                                @else
                                    0
                                @endif
                            </td>

                            <td class="text-end">
                                {{ number_format($row->overtime_day_salary ?? 0) }}
                            </td>

                            <td class="text-end">
                                {{ number_format($row->overtime_hour_salary ?? 0) }}
                            </td>

                            <td class="text-end">
                                {{ number_format($row->overtime_salary ?? 0) }}
                            </td>

                            <td class="text-end">
                                {{ number_format($row->allowance ?? 0) }}
                            </td>

                            <td class="text-end">
                                {{ number_format($row->bonus ?? 0) }}
                            </td>

                            <td class="text-end text-danger">
                                {{ number_format($row->insurance ?? 0) }}
                            </td>

                            <td class="text-end">
                                @if(($row->tax ?? 0) > 0)
                                    <span class="text-danger">
                                        {{ number_format($row->tax) }}
                                    </span>
                                @else
                                    0
                                @endif
                            </td>

                            <td class="text-end">
                                @if(($row->late_penalty_fee ?? 0) > 0)
                                    <span class="text-danger fw-semibold">− {{ number_format($row->late_penalty_fee) }}</span>
                                @else
                                    0
                                @endif
                            </td>

                            <td class="text-end">
                                <span class="fw-bold fs-5 {{ ($row->total_salary ?? 0) < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($row->total_salary ?? 0) }} VNĐ
                                </span>
                            </td>
                            @endunless

                            <td class="text-center">
                                <div class="d-inline-flex align-items-center gap-2 flex-wrap justify-content-center">
                                    @if($payroll && ! $hrWorkOnly)
                                    <a href="{{ route('payroll.show', $payroll) }}" class="btn btn-sm btn-outline-primary" title="Xem chi tiết">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @endif

                                    @if($payroll)

                                    @php
                                        $user = auth()->user();
                                        $canHrReview = $workflow->actorCanReview($user, $payroll);
                                        $canFinalApprove = $workflow->actorCanFinalApprove($user, $payroll);
                                        $canPay = $user->is_accountant && $workflow->canPay($payroll);
                                    @endphp

                                    @if($canHrReview && $periodVerified && ! $unlockPending)
                                        <form method="POST" action="{{ route('payroll.review', $payroll) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" title="Kiểm tra dữ liệu công" onclick="return confirm('Xác nhận đã kiểm tra ngày công, tăng ca, nghỉ phép của {{ optional($payroll->employee)->name }}?')">
                                                Kiểm tra phiếu đã tính
                                            </button>
                                        </form>
                                    @elseif($canHrReview)
                                        <span class="badge text-bg-secondary text-wrap" style="max-width:160px;">Chờ KT tính / HR xác nhận nguồn</span>
                                    @elseif($canFinalApprove)
                                        <form method="POST" action="{{ route('payroll.approve', $payroll) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" title="Phê duyệt cuối" onclick="return confirm('Phê duyệt cuối bảng lương của {{ optional($payroll->employee)->name }}?')">
                                                Phê duyệt cuối
                                            </button>
                                        </form>
                                    @elseif($workflow->isCalculated($payroll->status))
                                        <span class="badge text-bg-secondary text-wrap" style="max-width:160px;">Chờ HR kiểm tra phiếu</span>
                                    @elseif($workflow->isHrChecked($payroll->status))
                                        <span class="badge text-bg-info text-wrap" style="max-width:160px;">Chờ phê duyệt cuối</span>
                                    @elseif($payroll->status === 'payroll_issue' || $payroll->confirmation_status === 'issue_reported')
                                        <span class="badge text-bg-danger text-wrap" style="max-width:160px;" title="{{ $payroll->issue_report }}">Sự cố lương</span>
                                        @if(auth()->user()->is_hr)
                                            <a href="{{ route('payroll.issues.fix_form', $payroll) }}" class="btn btn-sm btn-danger">Khắc phục</a>
                                        @endif
                                    @elseif($workflow->isDirectorApproved($payroll->status))
                                        <span class="badge text-bg-warning text-wrap" style="max-width:160px;">Chờ xác nhận của nhân viên</span>
                                    @elseif($canPay)
                                        <a href="{{ route('payroll.payment.show', $payroll) }}" class="btn btn-sm btn-pay-soft">Thanh toán</a>
                                    @elseif($payroll->status === 'paid')
                                        <span class="badge text-bg-success">Đã thanh toán</span>
                                    @endif
                                    @else
                                        @if($unlockPending ?? false)
                                            <span class="badge text-bg-warning text-wrap" style="max-width:180px;">Chờ GĐ duyệt mở khóa</span>
                                        @elseif($periodLocked && ($periodVerified ?? false))
                                            <span class="badge text-bg-primary text-wrap" style="max-width:180px;">Đã kiểm tra — chờ kế toán tính</span>
                                        @elseif($periodLocked)
                                            <span class="badge text-bg-info text-wrap" style="max-width:180px;">Đã chốt — HR đang kiểm tra</span>
                                        @else
                                            <span class="badge text-bg-warning text-wrap" style="max-width:180px;">Kỳ đang mở</span>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="20" class="text-center py-5 text-muted">
                                @if(!empty($paymentFocus))
                                    Không có phiếu nhân viên đã xác nhận chờ thanh toán trong kỳ này.
                                @elseif(auth()->user()?->is_hr)
                                    Không có nhân viên đang làm để kiểm tra số liệu kỳ này.
                                @else
                                    Chưa có dữ liệu bảng lương.
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.btn-pay-soft{
    background:#bbf7d0 !important;
    color:#166534 !important;
    border:1px solid #86efac !important;
    font-weight:700;
}
.btn-pay-soft:hover{
    background:#86efac !important;
    color:#14532d !important;
}

body{
    background:#f5f7fb;
}

.card{
    border:none;
    border-radius:14px;
    overflow:hidden;
}

.card-body{
    padding:1.25rem;
}

.table{
    margin-bottom:0;
}

.payroll-table thead{
    background:#f8f9fa;
}

.payroll-table thead th{
    font-size:13px;
    font-weight:700;
    color:#6c757d;
    text-transform:uppercase;
    letter-spacing:.5px;
    padding:15px 12px;
    border-bottom:2px solid #e9ecef;
    white-space:nowrap;
}

.payroll-table tbody td{
    padding:15px 12px;
    vertical-align:middle;
    white-space:nowrap;
    border-color:#f1f3f5;
    font-size:14px;
}

.payroll-table tbody tr{
    transition:all .18s ease;
}

.payroll-table tbody tr:hover{
    background:#f8fbff;
}

.payroll-table tbody tr:hover td{
    background:#f8fbff;
}

.badge{
    font-size:13px;
    font-weight:500;
    padding:6px 12px;
    border-radius:6px;
}

.btn{
    border-radius:10px;
    font-weight:600;
}

.form-control,
.form-select{
    border-radius:10px;
    min-height:42px;
}

.text-end{
    font-variant-numeric:tabular-nums;
}

.text-success{
    font-weight:600;
    color:#198754!important;
}

.text-danger{
    font-weight:600;
    color:#dc3545!important;
}

.text-primary{
    color:#0d6efd!important;
}

.text-muted{
    font-size:14px;
    color:#6c757d!important;
}

.table-responsive{
    border-radius:12px;
}

h3{
    color:#212529;
}
</style>

@endsection