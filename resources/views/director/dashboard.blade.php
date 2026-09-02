@extends('layouts.app')
@section('title', 'Dashboard Giám đốc - SmartHR')

@section('content')
@php
    $monthLabel = sprintf('%02d/%d', $payroll['month'], $payroll['year']);
@endphp

<div class="emp-hero">
    <div>
        <p class="eyebrow" style="color:#c7d2fe;">Cổng giám đốc</p>
        <h1>Dashboard Giám đốc</h1>
        <p>Số liệu phục vụ phê duyệt và ra quyết định · kỳ {{ $monthLabel }}</p>
    </div>
    <div class="emp-hero-meta">
        <a class="emp-chip" href="{{ route('payroll.index', ['month' => $payroll['month'], 'year' => $payroll['year']]) }}"><i class="bi bi-check2-square"></i> Phê duyệt bảng lương</a>
        <a class="emp-chip" href="{{ route('contracts.index') }}"><i class="bi bi-pen"></i> Hợp đồng chờ ký</a>
    </div>
</div>

<div class="page-stack">
    <section class="dash-section">
        <h2 class="section-title">Nhân sự</h2>
        <div class="grid emp-kpis">
            <a href="{{ route('employees.index') }}" class="emp-kpi is-info">
                <div class="emp-kpi-head">
                    <h3 class="emp-kpi-label">Tổng nhân viên</h3>
                    <span class="emp-kpi-ico ico-info"><i class="bi bi-people"></i></span>
                </div>
                <div class="emp-kpi-value">{{ $people['total'] }}</div>
                <p class="emp-kpi-sub">Toàn hệ thống</p>
            </a>
            <article class="emp-kpi is-ok">
                <div class="emp-kpi-head">
                    <h3 class="emp-kpi-label">Đang làm</h3>
                    <span class="emp-kpi-ico ico-ok"><i class="bi bi-person-check"></i></span>
                </div>
                <div class="emp-kpi-value">{{ $people['active'] }}</div>
                <p class="emp-kpi-sub">Nhân viên đang làm việc</p>
            </article>
            <article class="emp-kpi is-warn">
                <div class="emp-kpi-head">
                    <h3 class="emp-kpi-label">Thử việc</h3>
                    <span class="emp-kpi-ico ico-warn"><i class="bi bi-hourglass-split"></i></span>
                </div>
                <div class="emp-kpi-value">{{ $people['probation'] }}</div>
                <p class="emp-kpi-sub">Hợp đồng thử việc</p>
            </article>
            <article class="emp-kpi is-danger">
                <div class="emp-kpi-head">
                    <h3 class="emp-kpi-label">Nghỉ việc</h3>
                    <span class="emp-kpi-ico ico-danger"><i class="bi bi-person-dash"></i></span>
                </div>
                <div class="emp-kpi-value">{{ $people['inactive'] }}</div>
                <p class="emp-kpi-sub">Không còn làm việc</p>
            </article>
            <article class="emp-kpi is-violet">
                <div class="emp-kpi-head">
                    <h3 class="emp-kpi-label">Biến động tháng này</h3>
                    <span class="emp-kpi-ico ico-violet"><i class="bi bi-person-plus"></i></span>
                </div>
                <div class="emp-kpi-value">{{ $people['joinedThisMonth'] }}</div>
                <p class="emp-kpi-sub">Nhân viên vào mới</p>
            </article>
        </div>
    </section>

    <section class="dash-section">
        <h2 class="section-title">Hợp đồng</h2>
        <div class="grid emp-kpis">
            <a href="{{ route('contracts.index') }}" class="emp-kpi {{ $contracts['waitingSign'] ? 'is-warn' : 'is-muted' }}">
                <div class="emp-kpi-head">
                    <h3 class="emp-kpi-label">Chờ ký</h3>
                    <span class="emp-kpi-ico {{ $contracts['waitingSign'] ? 'ico-warn' : 'ico-muted' }}"><i class="bi bi-pen"></i></span>
                </div>
                <div class="emp-kpi-value">{{ $contracts['waitingSign'] }}</div>
                <p class="emp-kpi-sub">Hợp đồng chờ chữ ký</p>
            </a>
            <article class="emp-kpi is-ok">
                <div class="emp-kpi-head">
                    <h3 class="emp-kpi-label">Đang hiệu lực</h3>
                    <span class="emp-kpi-ico ico-ok"><i class="bi bi-file-earmark-check"></i></span>
                </div>
                <div class="emp-kpi-value">{{ $contracts['active'] }}</div>
                <p class="emp-kpi-sub">Hợp đồng còn hạn</p>
            </article>
            <article class="emp-kpi {{ $contracts['expiringSoon'] ? 'is-danger' : 'is-muted' }}">
                <div class="emp-kpi-head">
                    <h3 class="emp-kpi-label">Sắp hết hạn (30 ngày)</h3>
                    <span class="emp-kpi-ico {{ $contracts['expiringSoon'] ? 'ico-danger' : 'ico-muted' }}"><i class="bi bi-exclamation-triangle"></i></span>
                </div>
                <div class="emp-kpi-value">{{ $contracts['expiringSoon'] }}</div>
                <p class="emp-kpi-sub">Cần gia hạn hoặc thanh lý</p>
            </article>
        </div>
    </section>

    <section class="dash-section">
        <h2 class="section-title">Lương tháng {{ $monthLabel }}</h2>
        <div class="grid emp-kpis">
            <article class="emp-kpi is-info">
                <div class="emp-kpi-head">
                    <h3 class="emp-kpi-label">Tổng quỹ lương</h3>
                    <span class="emp-kpi-ico ico-info"><i class="bi bi-cash-stack"></i></span>
                </div>
                <div class="emp-kpi-value is-money">{{ number_format($payroll['totalFund'], 0, ',', '.') }} đ</div>
                <p class="emp-kpi-sub">Tổng thực lĩnh kỳ {{ $monthLabel }}</p>
            </article>
            <a href="{{ route('payroll.index', ['month' => $payroll['month'], 'year' => $payroll['year']]) }}" class="emp-kpi {{ $payroll['awaitingDirector'] ? 'is-info' : 'is-muted' }}">
                <div class="emp-kpi-head">
                    <h3 class="emp-kpi-label">Chờ phê duyệt cuối</h3>
                    <span class="emp-kpi-ico ico-info"><i class="bi bi-check2-square"></i></span>
                </div>
                <div class="emp-kpi-value">{{ $payroll['awaitingDirector'] }}</div>
                <p class="emp-kpi-sub">HR đã kiểm tra — chờ Giám đốc</p>
            </a>
            <article class="emp-kpi {{ $payroll['issues'] ? 'is-danger' : 'is-muted' }}">
                <div class="emp-kpi-head">
                    <h3 class="emp-kpi-label">Sự cố cần duyệt lại</h3>
                    <span class="emp-kpi-ico {{ $payroll['issues'] ? 'ico-danger' : 'ico-muted' }}"><i class="bi bi-exclamation-octagon"></i></span>
                </div>
                <div class="emp-kpi-value">{{ $payroll['issues'] }}</div>
                <p class="emp-kpi-sub">Phiếu bị báo sự cố</p>
            </article>
            <article class="emp-kpi is-ok">
                <div class="emp-kpi-head">
                    <h3 class="emp-kpi-label">Đã duyệt</h3>
                    <span class="emp-kpi-ico ico-ok"><i class="bi bi-check-circle"></i></span>
                </div>
                <div class="emp-kpi-value">{{ $payroll['approved'] }}</div>
                <p class="emp-kpi-sub">Giám đốc đã duyệt</p>
            </article>
            <article class="emp-kpi is-warn">
                <div class="emp-kpi-head">
                    <h3 class="emp-kpi-label">Chờ NV xác nhận</h3>
                    <span class="emp-kpi-ico ico-warn"><i class="bi bi-person-check"></i></span>
                </div>
                <div class="emp-kpi-value">{{ $payroll['awaitingEmployee'] }}</div>
                <p class="emp-kpi-sub">Phiếu đã gửi nhân viên</p>
            </article>
            <article class="emp-kpi is-violet">
                <div class="emp-kpi-head">
                    <h3 class="emp-kpi-label">Chờ thanh toán</h3>
                    <span class="emp-kpi-ico ico-violet"><i class="bi bi-wallet2"></i></span>
                </div>
                <div class="emp-kpi-value">{{ $payroll['awaitingPayment'] }}</div>
                <p class="emp-kpi-sub">NV đã xác nhận</p>
            </article>
            <article class="emp-kpi is-ok">
                <div class="emp-kpi-head">
                    <h3 class="emp-kpi-label">Đã thanh toán</h3>
                    <span class="emp-kpi-ico ico-ok"><i class="bi bi-check2-circle"></i></span>
                </div>
                <div class="emp-kpi-value">{{ $payroll['paid'] }}</div>
                <p class="emp-kpi-sub">Kế toán đã chi</p>
            </article>
        </div>
    </section>

    <div class="split-2">
        <section class="dash-section">
            <h2 class="section-title">Nghỉ phép (báo cáo)</h2>
            <div class="grid emp-kpis cols-2">
                <article class="emp-kpi is-warn">
                    <div class="emp-kpi-head">
                        <h3 class="emp-kpi-label">Đơn chờ HR xử lý</h3>
                        <span class="emp-kpi-ico ico-warn"><i class="bi bi-journal-text"></i></span>
                    </div>
                    <div class="emp-kpi-value">{{ $leave['pending'] }}</div>
                </article>
                <article class="emp-kpi is-info">
                    <div class="emp-kpi-head">
                        <h3 class="emp-kpi-label">Số ngày nghỉ đã duyệt</h3>
                        <span class="emp-kpi-ico ico-info"><i class="bi bi-calendar-check"></i></span>
                    </div>
                    <div class="emp-kpi-value">{{ number_format($leave['days'], 1) }}</div>
                </article>
                <article class="emp-kpi is-ok">
                    <div class="emp-kpi-head">
                        <h3 class="emp-kpi-label">Nghỉ có lương</h3>
                        <span class="emp-kpi-ico ico-ok"><i class="bi bi-check-circle"></i></span>
                    </div>
                    <div class="emp-kpi-value">{{ number_format($leave['paidDays'], 1) }}</div>
                </article>
                <article class="emp-kpi is-danger">
                    <div class="emp-kpi-head">
                        <h3 class="emp-kpi-label">Nghỉ không lương</h3>
                        <span class="emp-kpi-ico ico-danger"><i class="bi bi-x-circle"></i></span>
                    </div>
                    <div class="emp-kpi-value">{{ number_format($leave['unpaidDays'], 1) }}</div>
                </article>
            </div>
            <p class="muted dash-note">Giám đốc xem báo cáo nghỉ phép. HR/quản lý xử lý từng đơn.</p>
        </section>

        <section class="dash-section">
            <h2 class="section-title">Chấm công tháng {{ $monthLabel }}</h2>
            <div class="grid emp-kpis cols-2">
                <article class="emp-kpi is-ok">
                    <div class="emp-kpi-head">
                        <h3 class="emp-kpi-label">Đủ công (phiếu lương)</h3>
                        <span class="emp-kpi-ico ico-ok"><i class="bi bi-check2-all"></i></span>
                    </div>
                    <div class="emp-kpi-value">{{ $attendance['full'] }}</div>
                </article>
                <article class="emp-kpi is-warn">
                    <div class="emp-kpi-head">
                        <h3 class="emp-kpi-label">Thiếu công (phiếu lương)</h3>
                        <span class="emp-kpi-ico ico-warn"><i class="bi bi-dash-circle"></i></span>
                    </div>
                    <div class="emp-kpi-value">{{ $attendance['short'] }}</div>
                </article>
                <article class="emp-kpi is-warn">
                    <div class="emp-kpi-head">
                        <h3 class="emp-kpi-label">Đi muộn (lượt)</h3>
                        <span class="emp-kpi-ico ico-warn"><i class="bi bi-clock-history"></i></span>
                    </div>
                    <div class="emp-kpi-value">{{ $attendance['late'] }}</div>
                </article>
                <article class="emp-kpi is-danger">
                    <div class="emp-kpi-head">
                        <h3 class="emp-kpi-label">Vắng (lượt)</h3>
                        <span class="emp-kpi-ico ico-danger"><i class="bi bi-person-x"></i></span>
                    </div>
                    <div class="emp-kpi-value">{{ $attendance['absent'] }}</div>
                </article>
            </div>
        </section>
    </div>

    <div class="card">
        <div class="card-head">
            <h2 class="card-title">Hợp đồng sắp hết hạn</h2>
        </div>
        @if($expiringContracts->isEmpty())
            <div class="empty">Không có hợp đồng hết hạn trong 30 ngày tới.</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Nhân viên</th>
                        <th>Phòng ban</th>
                        <th>Ngày hết hạn</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($expiringContracts as $contract)
                        <tr>
                            <td>{{ optional($contract->employee)->name ?? '—' }}</td>
                            <td>{{ optional(optional($contract->employee)->department)->name ?? '—' }}</td>
                            <td>{{ optional($contract->end_date)->format('d/m/Y') ?? '—' }}</td>
                            <td><a class="btn link" href="{{ route('contracts.show', $contract) }}">Xem</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
