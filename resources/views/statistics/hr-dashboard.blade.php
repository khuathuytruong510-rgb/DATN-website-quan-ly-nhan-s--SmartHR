@extends('layouts.app')

@section('title', 'Báo cáo tổng hợp nhân sự - SmartHR')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
        <h1 class="mb-0">Báo cáo tổng hợp nhân sự</h1>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-printer"></i> In báo cáo
            </button>
            <a href="{{ route('hr-dashboard.export', array_merge(request()->query(), ['format' => 'excel'])) }}" class="btn btn-outline-success btn-sm">
                <i class="bi bi-file-earmark-excel"></i> Xuất Excel
            </a>
            <a href="{{ route('hr-dashboard.export', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-file-earmark-pdf"></i> Xuất PDF
            </a>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="card mb-4">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label mb-0 small">Khoảng thời gian</label>
                    <select name="period" class="form-select form-select-sm" id="periodSelect" onchange="toggleCustomDate()">
                        <option value="today" {{ request('period') == 'today' ? 'selected' : '' }}>Hôm nay</option>
                        <option value="7days" {{ request('period') == '7days' ? 'selected' : '' }}>7 ngày gần đây</option>
                        <option value="month" {{ request('period', 'month') == 'month' ? 'selected' : '' }}>Tháng này</option>
                        <option value="quarter" {{ request('period') == 'quarter' ? 'selected' : '' }}>Quý này</option>
                        <option value="year" {{ request('period') == 'year' ? 'selected' : '' }}>Năm nay</option>
                        <option value="custom" {{ request('period') == 'custom' ? 'selected' : '' }}>Tùy chọn</option>
                    </select>
                </div>
                <div class="col-auto custom-date" style="{{ request('period') != 'custom' ? 'display:none' : '' }}">
                    <label class="form-label mb-0 small">Từ ngày</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from', now()->startOfMonth()->format('Y-m-d')) }}">
                </div>
                <div class="col-auto custom-date" style="{{ request('period') != 'custom' ? 'display:none' : '' }}">
                    <label class="form-label mb-0 small">Đến ngày</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to', now()->endOfMonth()->format('Y-m-d')) }}">
                </div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i> Lọc</button>
                </div>
                <div class="col-auto">
                    <span class="badge bg-info fs-6">{{ $periodLabel }}</span>
                </div>
            </form>
        </div>
    </div>

    {{-- 1. TỔNG QUAN NHÂN SỰ --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-primary h-100">
                <div class="card-body text-center">
                    <div class="text-primary fs-2 fw-bold">{{ $hrOverview['totalEmployees'] }}</div>
                    <div class="text-muted small">Tổng nhân viên</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info h-100">
                <div class="card-body text-center">
                    <div class="text-info fs-2 fw-bold">{{ $hrOverview['totalDepartments'] }}</div>
                    <div class="text-muted small">Phòng ban</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning h-100">
                <div class="card-body text-center">
                    <div class="text-warning fs-2 fw-bold">{{ $hrOverview['totalPositions'] }}</div>
                    <div class="text-muted small">Chức vụ</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success h-100">
                <div class="card-body text-center">
                    <div class="text-success fs-2 fw-bold">{{ $hrOverview['newThisMonth'] }}</div>
                    <div class="text-muted small">Nhân viên mới</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="fs-4 fw-bold text-success">{{ $hrOverview['activeEmployees'] }}</div>
                    <div class="text-muted small">Đang làm việc</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="fs-4 fw-bold text-danger">{{ $hrOverview['inactiveEmployees'] }}</div>
                    <div class="text-muted small">Nghỉ việc</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="fs-4 fw-bold text-warning">{{ $hrOverview['probationEmployees'] }}</div>
                    <div class="text-muted small">Thử việc</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="fs-4 fw-bold text-info">{{ $hrOverview['internEmployees'] }}</div>
                    <div class="text-muted small">Thực tập</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="fs-4 fw-bold text-primary">{{ $accountStats['total'] }}</div>
                    <div class="text-muted small">Tài khoản hệ thống</div>
                    <div class="small mt-1">
                        <span class="badge bg-success">{{ $accountStats['active'] }} hoạt động</span>
                        <span class="badge bg-danger">{{ $accountStats['locked'] }} khóa</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. THỐNG KÊ PHÒNG BAN --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0"><i class="bi bi-pie-chart"></i> Tỷ lệ nhân viên theo phòng ban</h6></div>
                <div class="card-body">
                    <canvas id="deptPieChart" height="280"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0"><i class="bi bi-bar-chart"></i> Số nhân viên theo phòng ban</h6></div>
                <div class="card-body">
                    <canvas id="deptBarChart" height="280"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Chi tiết phòng ban</h6>
            <span class="badge bg-success">Đông nhất: {{ $departmentStats['maxDepartment'] }} ({{ $departmentStats['maxCount'] }} NV)</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Phòng ban</th>
                            <th class="text-center">Số nhân viên</th>
                            <th>Tỷ lệ</th>
                            <th class="text-center">Biểu đồ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($departmentStats['departments'] as $dept)
                        <tr>
                            <td class="fw-medium">{{ $dept['name'] }}</td>
                            <td class="text-center">
                                <span class="badge bg-primary">{{ $dept['count'] }}</span>
                            </td>
                            <td>{{ $dept['percentage'] }}%</td>
                            <td style="width: 40%">
                                <div class="progress" style="height: 18px;">
                                    <div class="progress-bar bg-primary" style="width: {{ $dept['percentage'] }}%">{{ $dept['percentage'] }}%</div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">Không có dữ liệu phòng ban</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- 3. THỐNG KÊ CHẤM CÔNG --}}
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="fs-4 fw-bold text-primary">{{ number_format($attendanceStats['totalWorkDays']) }}</div>
                    <div class="text-muted small">Tổng ngày công</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="fs-4 fw-bold text-success">{{ number_format($attendanceStats['paidLeaves'], 1) }}</div>
                    <div class="text-muted small">Nghỉ phép</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="fs-4 fw-bold text-warning">{{ number_format($attendanceStats['unpaidLeaves'], 1) }}</div>
                    <div class="text-muted small">Nghỉ không phép</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="fs-4 fw-bold text-danger">{{ number_format($attendanceStats['totalLate']) }}</div>
                    <div class="text-muted small">Đi muộn</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="fs-4 fw-bold text-info">{{ number_format($attendanceStats['totalEarlyLeave']) }}</div>
                    <div class="text-muted small">Về sớm</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="fs-4 fw-bold text-secondary">{{ number_format($attendanceStats['totalOvertimeHours'], 1) }}h</div>
                    <div class="text-muted small">Giờ làm thêm</div>
                </div>
            </div>
        </div>
    </div>

    {{-- 4. THỐNG KÊ LƯƠNG --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-primary h-100">
                <div class="card-body text-center">
                    <div class="text-primary fs-4 fw-bold">{{ number_format($payrollStats['totalFund'], 0, ',', '.') }}đ</div>
                    <div class="text-muted small">Tổng quỹ lương</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success h-100">
                <div class="card-body text-center">
                    <div class="text-success fs-4 fw-bold">{{ number_format($payrollStats['avgSalary'], 0, ',', '.') }}đ</div>
                    <div class="text-muted small">Lương trung bình</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning h-100">
                <div class="card-body text-center">
                    <div class="text-warning fs-4 fw-bold">{{ number_format($payrollStats['maxSalary'], 0, ',', '.') }}đ</div>
                    <div class="text-muted small">Lương cao nhất</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info h-100">
                <div class="card-body text-center">
                    <div class="text-info fs-4 fw-bold">{{ number_format($payrollStats['minSalary'], 0, ',', '.') }}đ</div>
                    <div class="text-muted small">Lương thấp nhất</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="fs-5 fw-bold text-success">{{ number_format($payrollStats['totalAllowance'], 0, ',', '.') }}đ</div>
                    <div class="text-muted small">Tổng phụ cấp</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="fs-5 fw-bold text-danger">{{ number_format($payrollStats['totalDeduction'], 0, ',', '.') }}đ</div>
                    <div class="text-muted small">Tổng khấu trừ</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="fs-5 fw-bold text-warning">{{ number_format($payrollStats['totalBonus'], 0, ',', '.') }}đ</div>
                    <div class="text-muted small">Tổng thưởng</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="fs-5 fw-bold text-primary">{{ number_format($payrollStats['totalNet'], 0, ',', '.') }}đ</div>
                    <div class="text-muted small">Tổng tiền thực lĩnh</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0"><i class="bi bi-graph-up"></i> Quỹ lương theo tháng (12 tháng)</h6></div>
                <div class="card-body">
                    <canvas id="payrollTrendChart" height="250"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0"><i class="bi bi-building"></i> Lương theo phòng ban</h6></div>
                <div class="card-body">
                    <canvas id="payrollDeptChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- 5. THỐNG KÊ HỢP ĐỒNG --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-primary h-100">
                <div class="card-body text-center">
                    <div class="text-primary fs-2 fw-bold">{{ $contractStats['total'] }}</div>
                    <div class="text-muted small">Tổng hợp đồng</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning h-100">
                <div class="card-body text-center">
                    <div class="text-warning fs-2 fw-bold">{{ $contractStats['expiringSoon'] }}</div>
                    <div class="text-muted small">Sắp hết hạn (30 ngày)</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-danger h-100">
                <div class="card-body text-center">
                    <div class="text-danger fs-2 fw-bold">{{ $contractStats['expired'] }}</div>
                    <div class="text-muted small">Đã hết hạn</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success h-100">
                <div class="card-body text-center">
                    <div class="text-success fs-2 fw-bold">{{ $contractStats['active'] }}</div>
                    <div class="text-muted small">Đang hiệu lực</div>
                </div>
            </div>
        </div>
    </div>

    @if($contractStats['byType']->count())
    <div class="card mb-4">
        <div class="card-header"><h6 class="mb-0">Hợp đồng theo loại</h6></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <canvas id="contractTypeChart" height="200"></canvas>
                </div>
                <div class="col-md-8">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr><th>Loại hợp đồng</th><th class="text-center">Số lượng</th><th>Tỷ lệ</th></tr>
                            </thead>
                            <tbody>
                                @php $totalContracts = $contractStats['byType']->sum(); @endphp
                                @foreach($contractStats['byType'] as $type => $count)
                                @php
                                    $typeLabel = match($type) {
                                        'intern' => 'Thực tập',
                                        'probation' => 'Thử việc',
                                        'indefinite' => 'Lao động chính thức',
                                        'fixed_term' => 'Hợp đồng xác định thời hạn',
                                        default => ucfirst($type)
                                    };
                                    $pct = $totalContracts > 0 ? round(($count / $totalContracts) * 100, 1) : 0;
                                @endphp
                                <tr>
                                    <td class="fw-medium">{{ $typeLabel }}</td>
                                    <td class="text-center"><span class="badge bg-primary">{{ $count }}</span></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 16px;">
                                                <div class="progress-bar bg-info" style="width: {{ $pct }}%"></div>
                                            </div>
                                            <span class="small">{{ $pct }}%</span>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- 7. THỐNG KÊ ĐƠN TỪ --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0"><i class="bi bi-file-earmark-text"></i> Thống kê đơn từ</h6></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Loại đơn</th>
                                    <th class="text-center">Tổng số</th>
                                    <th class="text-center">Chờ duyệt</th>
                                    <th class="text-center">Đã duyệt</th>
                                    <th class="text-center">Từ chối</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="fw-medium">Đơn nghỉ phép</td>
                                    <td class="text-center">{{ $requestStats['totalLeave'] }}</td>
                                    <td class="text-center"><span class="badge bg-warning text-dark">Chờ</span></td>
                                    <td class="text-center"><span class="badge bg-success">Duyệt</span></td>
                                    <td class="text-center"><span class="badge bg-danger">Từ chối</span></td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">Đơn tăng ca</td>
                                    <td class="text-center">{{ $requestStats['totalOvertime'] }}</td>
                                    <td class="text-center"><span class="badge bg-warning text-dark">Chờ</span></td>
                                    <td class="text-center"><span class="badge bg-success">Duyệt</span></td>
                                    <td class="text-center"><span class="badge bg-danger">Từ chối</span></td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">Đơn ứng lương</td>
                                    <td class="text-center">{{ $requestStats['totalAdvance'] }}</td>
                                    <td class="text-center"><span class="badge bg-warning text-dark">Chờ</span></td>
                                    <td class="text-center"><span class="badge bg-success">Duyệt</span></td>
                                    <td class="text-center"><span class="badge bg-danger">Từ chối</span></td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">Đơn hỗ trợ</td>
                                    <td class="text-center">{{ $requestStats['totalSupport'] }}</td>
                                    <td class="text-center"><span class="badge bg-warning text-dark">Chờ</span></td>
                                    <td class="text-center"><span class="badge bg-success">Duyệt</span></td>
                                    <td class="text-center"><span class="badge bg-danger">Từ chối</span></td>
                                </tr>
                            </tbody>
                            <tfoot class="table-dark">
                                <tr>
                                    <th>Tổng cộng</th>
                                    <th class="text-center">{{ $requestStats['totalLeave'] + $requestStats['totalOvertime'] + $requestStats['totalAdvance'] + $requestStats['totalSupport'] }}</th>
                                    <th class="text-center">{{ $requestStats['pendingAll'] }}</th>
                                    <th class="text-center">{{ $requestStats['approvedAll'] }}</th>
                                    <th class="text-center">{{ $requestStats['rejectedAll'] }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header"><h6 class="mb-0"><i class="bi bi-clipboard-check"></i> Tổng quan đơn từ</h6></div>
                <div class="card-body">
                    <canvas id="requestChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- 8. THỐNG KÊ TÀI KHOẢN --}}
    <div class="card mb-4">
        <div class="card-header"><h6 class="mb-0"><i class="bi bi-person-gear"></i> Thống kê tài khoản</h6></div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-2">
                    <div class="fs-3 fw-bold text-primary">{{ $accountStats['total'] }}</div>
                    <div class="text-muted small">Tổng tài khoản</div>
                </div>
                <div class="col-md-2">
                    <div class="fs-3 fw-bold text-danger">{{ $accountStats['admin'] }}</div>
                    <div class="text-muted small">Admin</div>
                </div>
                <div class="col-md-2">
                    <div class="fs-3 fw-bold" style="color:#7c3aed;">{{ $accountStats['director'] ?? 0 }}</div>
                    <div class="text-muted small">Giám đốc</div>
                </div>
                <div class="col-md-2">
                    <div class="fs-3 fw-bold text-info">{{ $accountStats['hr'] }}</div>
                    <div class="text-muted small">HR</div>
                </div>
                <div class="col-md-2">
                    <div class="fs-3 fw-bold text-warning">{{ $accountStats['accountant'] }}</div>
                    <div class="text-muted small">Kế toán</div>
                </div>
                <div class="col-md-2">
                    <div class="fs-3 fw-bold text-secondary">{{ $accountStats['employee'] }}</div>
                    <div class="text-muted small">Nhân viên</div>
                </div>
                <div class="col-md-2">
                    <div class="fs-3 fw-bold text-success">{{ $accountStats['active'] }}</div>
                    <div class="text-muted small">Đang hoạt động</div>
                </div>
            </div>
        </div>
    </div>

    {{-- 9. THỐNG KÊ KHEN THƯỞNG - KỶ LUẬT --}}
    <div class="card mb-4">
        <div class="card-header"><h6 class="mb-0"><i class="bi bi-award"></i> Thống kê khen thưởng - kỷ luật</h6></div>
        <div class="card-body">
            <div class="row text-center">
                <div class="col-md-4">
                    <div class="fs-3 fw-bold text-success">{{ $rewardStats['totalRewards'] }}</div>
                    <div class="text-muted small">Quyết định khen thưởng</div>
                </div>
                <div class="col-md-4">
                    <div class="fs-3 fw-bold text-danger">{{ $rewardStats['totalDiscipline'] }}</div>
                    <div class="text-muted small">Quyết định kỷ luật</div>
                </div>
                <div class="col-md-4">
                    <div class="fs-3 fw-bold text-info">{{ $rewardStats['topRewarded'] }}</div>
                    <div class="text-muted small">Nhân viên được thưởng nhiều nhất</div>
                </div>
            </div>
        </div>
    </div>

    {{-- 10. BIẾNG ĐỒ NHÂN VIÊN MỚI --}}
    <div class="card mb-4">
        <div class="card-header"><h6 class="mb-0"><i class="bi bi-person-plus"></i> Nhân viên mới theo tháng (12 tháng)</h6></div>
        <div class="card-body">
            <canvas id="newEmployeeChart" height="100"></canvas>
        </div>
    </div>
</div>

@push('scripts')
@php
    $monthlyPayrollTrend = collect($monthlyPayrollTrend);
    $monthlyNewEmployees = collect($monthlyNewEmployees);
    $deptPayroll = collect($payrollStats['departmentPayroll'] ?? []);
    $departments = collect($departmentStats['departments'] ?? []);
    $contractByType = collect($contractStats['byType'] ?? []);

    $trendLabels = $monthlyPayrollTrend->pluck('label')->values()->all();
    $trendData = $monthlyPayrollTrend->pluck('total')->map(fn ($v) => round($v / 1000000, 1))->values()->all();
    $newEmpLabels = $monthlyNewEmployees->pluck('label')->values()->all();
    $newEmpData = $monthlyNewEmployees->pluck('count')->values()->all();
    $deptLabels = $departments->pluck('name')->values()->all();
    $deptCounts = $departments->pluck('count')->values()->all();
    $deptPayLabels = $deptPayroll->pluck('department_name')->values()->all();
    $deptPayData = $deptPayroll->pluck('total_net')->map(fn ($v) => round($v / 1000000, 1))->values()->all();
    $ctLabels = $contractByType->keys()->map(fn ($k) => match ($k) {
        'intern' => 'Thực tập',
        'probation' => 'Thử việc',
        'indefinite' => 'Chính thức',
        'fixed_term' => 'XDH',
        default => ucfirst((string) $k),
    })->values()->all();
    $ctData = $contractByType->values()->all();
@endphp
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Department Pie Chart
    const pieCtx = document.getElementById('deptPieChart');
    if (pieCtx) {
        new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: @json($deptLabels),
                datasets: [{
                    data: @json($deptCounts),
                    backgroundColor: [
                        '#0d6efd','#198754','#ffc107','#dc3545','#6f42c1',
                        '#0dcaf0','#fd7e14','#20c997','#6610f2','#d63384',
                        '#0d6efd','#198754'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'right', labels: { boxWidth: 12, padding: 8 } }
                }
            }
        });
    }

    // Department Bar Chart
    const barCtx = document.getElementById('deptBarChart');
    if (barCtx) {
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: @json($deptLabels),
                datasets: [{
                    label: 'Số nhân viên',
                    data: @json($deptCounts),
                    backgroundColor: '#0d6efd',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    }

    // Payroll Trend Chart
    const trendCtx = document.getElementById('payrollTrendChart');
    if (trendCtx) {
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: @json($trendLabels),
                datasets: [{
                    label: 'Quỹ lương (triệu VNĐ)',
                    data: @json($trendData),
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13,110,253,0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, title: { display: true, text: 'Triệu VNĐ' } } }
            }
        });
    }

    // Payroll by Department Chart
    const deptPayCtx = document.getElementById('payrollDeptChart');
    if (deptPayCtx) {
        new Chart(deptPayCtx, {
            type: 'bar',
            data: {
                labels: @json($deptPayLabels),
                datasets: [{
                    label: 'Tổng lương (triệu VNĐ)',
                    data: @json($deptPayData),
                    backgroundColor: ['#0d6efd','#198754','#ffc107','#dc3545','#6f42c1','#0dcaf0','#fd7e14'],
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, title: { display: true, text: 'Triệu VNĐ' } } }
            }
        });
    }

    // Contract Type Chart
    const ctCtx = document.getElementById('contractTypeChart');
    if (ctCtx) {
        const ctLabels = @json($ctLabels);
        const ctData = @json($ctData);
        new Chart(ctCtx, {
            type: 'doughnut',
            data: {
                labels: ctLabels,
                datasets: [{
                    data: ctData,
                    backgroundColor: ['#0d6efd','#ffc107','#198754','#dc3545','#6f42c1'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }
            }
        });
    }

    // Request Chart
    const reqCtx = document.getElementById('requestChart');
    if (reqCtx) {
        new Chart(reqCtx, {
            type: 'doughnut',
            data: {
                labels: ['Chờ duyệt', 'Đã duyệt', 'Từ chối'],
                datasets: [{
                    data: [{{ $requestStats['pendingAll'] }}, {{ $requestStats['approvedAll'] }}, {{ $requestStats['rejectedAll'] }}],
                    backgroundColor: ['#ffc107', '#198754', '#dc3545'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }
            }
        });
    }

    // New Employee Chart
    const neCtx = document.getElementById('newEmployeeChart');
    if (neCtx) {
        new Chart(neCtx, {
            type: 'bar',
            data: {
                labels: @json($newEmpLabels),
                datasets: [{
                    label: 'Nhân viên mới',
                    data: @json($newEmpData),
                    backgroundColor: '#198754',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    }
});

function toggleCustomDate() {
    var period = document.getElementById('periodSelect').value;
    var customFields = document.querySelectorAll('.custom-date');
    customFields.forEach(function(el) {
        el.style.display = period === 'custom' ? '' : 'none';
    });
}
</script>
@endpush

<style>
@media print {
    .sidebar, .topbar, .btn, form { display: none !important; }
    .shell { grid-template-columns: 1fr !important; }
    .content { padding: 0 !important; }
    .card { break-inside: avoid; box-shadow: none !important; border: 1px solid #ddd !important; }
}
</style>
@endsection
