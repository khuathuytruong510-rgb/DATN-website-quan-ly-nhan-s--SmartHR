@extends('layouts.app')
@section('title', 'Dashboard - SmartHR')

@section('content')
<div>

<div class="emp-hero">
    <div>
        <h1>Dashboard</h1>
        <p>Tổng quan hệ thống quản lý nhân sự</p>
    </div>
    <div class="emp-hero-meta">
        <button type="button" class="emp-chip" onclick="window.print()" style="cursor:pointer;border:1px solid rgba(255,255,255,.18);color:#fff;">In báo cáo</button>
        <a class="emp-chip" href="{{ route('hr-dashboard.export', ['format'=>'excel','period'=>'all']) }}">Xuất Excel</a>
        <a class="emp-chip" href="{{ route('hr-dashboard.export', ['format'=>'pdf','period'=>'all']) }}">Xuất PDF</a>
    </div>
</div>

<section class="grid emp-kpis">
    <article class="emp-kpi is-info">
        <div class="emp-kpi-head">
            <h2 class="emp-kpi-label">Tổng nhân viên</h2>
            <span class="emp-kpi-ico ico-info"><i class="bi bi-people"></i></span>
        </div>
        <div class="emp-kpi-value">{{ $hrOverview['totalEmployees'] }}</div>
        <p class="emp-kpi-sub">Toàn bộ hồ sơ nhân sự</p>
    </article>
    <article class="emp-kpi is-violet">
        <div class="emp-kpi-head">
            <h2 class="emp-kpi-label">Phòng ban</h2>
            <span class="emp-kpi-ico ico-violet"><i class="bi bi-building"></i></span>
        </div>
        <div class="emp-kpi-value">{{ $hrOverview['totalDepartments'] }}</div>
        <p class="emp-kpi-sub">Cơ cấu tổ chức</p>
    </article>
    <article class="emp-kpi is-warn">
        <div class="emp-kpi-head">
            <h2 class="emp-kpi-label">Chức vụ</h2>
            <span class="emp-kpi-ico ico-warn"><i class="bi bi-briefcase"></i></span>
        </div>
        <div class="emp-kpi-value">{{ $hrOverview['totalPositions'] }}</div>
        <p class="emp-kpi-sub">Vị trí công việc</p>
    </article>
    <article class="emp-kpi is-ok">
        <div class="emp-kpi-head">
            <h2 class="emp-kpi-label">Đang làm việc</h2>
            <span class="emp-kpi-ico ico-ok"><i class="bi bi-person-check"></i></span>
        </div>
        <div class="emp-kpi-value">{{ $hrOverview['activeEmployees'] }}</div>
        <p class="emp-kpi-sub">Nhân viên active</p>
    </article>
    <article class="emp-kpi is-danger">
        <div class="emp-kpi-head">
            <h2 class="emp-kpi-label">Nghỉ việc</h2>
            <span class="emp-kpi-ico ico-danger"><i class="bi bi-person-dash"></i></span>
        </div>
        <div class="emp-kpi-value">{{ $hrOverview['inactiveEmployees'] }}</div>
        <p class="emp-kpi-sub">Không còn làm việc</p>
    </article>
    <article class="emp-kpi is-warn">
        <div class="emp-kpi-head">
            <h2 class="emp-kpi-label">Thử việc / Thực tập</h2>
            <span class="emp-kpi-ico ico-warn"><i class="bi bi-hourglass-split"></i></span>
        </div>
        <div class="emp-kpi-value">{{ $hrOverview['probationEmployees'] }} / {{ $hrOverview['internEmployees'] }}</div>
        <p class="emp-kpi-sub">Thử việc · Thực tập</p>
    </article>
    <article class="emp-kpi is-info">
        <div class="emp-kpi-head">
            <h2 class="emp-kpi-label">Tài khoản</h2>
            <span class="emp-kpi-ico ico-info"><i class="bi bi-person-badge"></i></span>
        </div>
        <div class="emp-kpi-value">{{ $accountStats['total'] }}</div>
        <p class="emp-kpi-sub"><span class="emp-badge ok">{{ $accountStats['active'] }} HĐ</span> <span class="emp-badge warn">{{ $accountStats['locked'] }} khoá</span></p>
    </article>
    <article class="emp-kpi {{ $contractStats['expiringSoon'] ? 'is-danger' : 'is-muted' }}">
        <div class="emp-kpi-head">
            <h2 class="emp-kpi-label">HĐ sắp hết hạn</h2>
            <span class="emp-kpi-ico {{ $contractStats['expiringSoon'] ? 'ico-danger' : 'ico-muted' }}"><i class="bi bi-exclamation-triangle"></i></span>
        </div>
        <div class="emp-kpi-value">{{ $contractStats['expiringSoon'] }}</div>
        <p class="emp-kpi-sub">Trong 30 ngày tới</p>
    </article>
</section>

{{-- 2. Biểu đồ phòng ban --}}
<h2 class="section-title">Phòng ban</h2>
<div style="display:grid;grid-template-columns:1fr 1.4fr;gap:16px;margin-bottom:20px;">
    <div class="card" style="padding:18px;">
        <div style="font-weight:700;margin-bottom:14px;">📊 Tỷ lệ nhân viên theo phòng ban</div>
        <canvas id="deptPieChart" height="260"></canvas>
    </div>
    <div class="card" style="padding:18px;">
        <div style="font-weight:700;margin-bottom:14px;">📈 Số nhân viên theo phòng ban</div>
        <canvas id="deptBarChart" height="260"></canvas>
    </div>
</div>

{{-- Bảng chi tiết phòng ban --}}
<div class="card" style="margin-bottom:20px;">
    <div style="font-weight:700;margin-bottom:14px;display:flex;justify-content:space-between;align-items:center;">
        <span>Chi tiết phòng ban</span>
        <span class="badge" style="background:#dcfce7;color:#166534;">Đông nhất: {{ $departmentStats['maxDepartment'] }} ({{ $departmentStats['maxCount'] }} NV)</span>
    </div>
    <table>
        <thead>
            <tr>
                <th>Phòng ban</th>
                <th style="text-align:center;">Số nhân viên</th>
                <th>Tỷ lệ</th>
                <th style="width:40%;">Biểu đồ</th>
            </tr>
        </thead>
        <tbody>
            @forelse($departmentStats['departments'] as $dept)
            <tr>
                <td style="font-weight:600;">{{ $dept['name'] }}</td>
                <td style="text-align:center;"><span class="badge">{{ $dept['count'] }}</span></td>
                <td>{{ $dept['percentage'] }}%</td>
                <td>
                    <div style="background:#f1f5f9;border-radius:999px;height:16px;overflow:hidden;">
                        <div style="width:{{ $dept['percentage'] }}%;background:#2563eb;height:100%;border-radius:999px;"></div>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:20px;">Không có dữ liệu</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- 3. Chấm công --}}
<h2 class="section-title">Chấm công</h2>
<div style="display:grid;grid-template-columns:repeat(6,1fr);gap:12px;margin-bottom:20px;">
    @foreach([
        ['Tổng ngày công', number_format($attendanceStats['totalWorkDays']), '#2563eb'],
        ['Nghỉ phép', number_format($attendanceStats['paidLeaves'],1), '#16a34a'],
        ['Nghỉ không phép', number_format($attendanceStats['unpaidLeaves'],1), '#d97706'],
        ['Đi muộn', number_format($attendanceStats['totalLate']), '#dc2626'],
        ['Về sớm', number_format($attendanceStats['totalEarlyLeave']), '#0ea5e9'],
        ['Giờ làm thêm', number_format($attendanceStats['totalOvertimeHours'],1).'h', '#7c3aed'],
    ] as [$label, $val, $color])
    <div class="card" style="padding:14px;text-align:center;">
        <div style="font-size:22px;font-weight:800;color:{{ $color }};">{{ $val }}</div>
        <div style="font-size:12px;color:#64748b;margin-top:4px;">{{ $label }}</div>
    </div>
    @endforeach
</div>

{{-- 4. Lương --}}
<h2 class="section-title">Lương</h2>
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:14px;">
    @foreach([
        ['Tổng quỹ lương', number_format($payrollStats['totalFund'],0,',','.').'₫', '#2563eb'],
        ['Lương trung bình', number_format($payrollStats['avgSalary'],0,',','.').'₫', '#16a34a'],
        ['Lương cao nhất', number_format($payrollStats['maxSalary'],0,',','.').'₫', '#d97706'],
        ['Lương thấp nhất', number_format($payrollStats['minSalary'],0,',','.').'₫', '#0ea5e9'],
    ] as [$label, $val, $color])
    <div class="card" style="padding:18px;text-align:center;border-color:{{ $color }}33;">
        <div style="font-size:18px;font-weight:800;color:{{ $color }};">{{ $val }}</div>
        <div style="font-size:12px;color:#64748b;margin-top:4px;">{{ $label }}</div>
    </div>
    @endforeach
</div>
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;">
    @foreach([
        ['Tổng phụ cấp', number_format($payrollStats['totalAllowance'],0,',','.').'₫', '#16a34a'],
        ['Tổng khấu trừ', number_format($payrollStats['totalDeduction'],0,',','.').'₫', '#dc2626'],
        ['Tổng thưởng', number_format($payrollStats['totalBonus'],0,',','.').'₫', '#d97706'],
        ['Tổng thực lĩnh', number_format($payrollStats['totalNet'],0,',','.').'₫', '#2563eb'],
    ] as [$label, $val, $color])
    <div class="card" style="padding:16px;text-align:center;">
        <div style="font-size:16px;font-weight:700;color:{{ $color }};">{{ $val }}</div>
        <div style="font-size:12px;color:#64748b;margin-top:4px;">{{ $label }}</div>
    </div>
    @endforeach
</div>

{{-- Biểu đồ lương --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
    <div class="card" style="padding:18px;">
        <div style="font-weight:700;margin-bottom:14px;">📈 Quỹ lương theo tháng (12 tháng)</div>
        <canvas id="payrollTrendChart" height="220"></canvas>
    </div>
    <div class="card" style="padding:18px;">
        <div style="font-weight:700;margin-bottom:14px;">🏢 Lương theo phòng ban</div>
        <canvas id="payrollDeptChart" height="220"></canvas>
    </div>
</div>

{{-- 5. Hợp đồng --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;">
    @foreach([
        ['Tổng hợp đồng', $contractStats['total'], '#2563eb'],
        ['Sắp hết hạn (30 ngày)', $contractStats['expiringSoon'], '#d97706'],
        ['Đã hết hạn', $contractStats['expired'], '#dc2626'],
        ['Đang hiệu lực', $contractStats['active'], '#16a34a'],
    ] as [$label, $val, $color])
    <div class="card" style="padding:18px;text-align:center;border-color:{{ $color }}33;">
        <div style="font-size:32px;font-weight:900;color:{{ $color }};">{{ $val }}</div>
        <div style="font-size:13px;color:#64748b;margin-top:4px;">{{ $label }}</div>
    </div>
    @endforeach
</div>

{{-- 6. Đơn từ + biểu đồ --}}
<div style="display:grid;grid-template-columns:1.4fr 1fr;gap:16px;margin-bottom:20px;">
    <div class="card" style="padding:18px;">
        <div style="font-weight:700;margin-bottom:14px;">📋 Thống kê đơn từ</div>
        <table>
            <thead>
                <tr>
                    <th>Loại đơn</th>
                    <th style="text-align:center;">Tổng</th>
                    <th style="text-align:center;">Chờ duyệt</th>
                    <th style="text-align:center;">Đã duyệt</th>
                    <th style="text-align:center;">Từ chối</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Nghỉ phép</td>
                    <td style="text-align:center;">{{ $requestStats['totalLeave'] }}</td>
                    <td style="text-align:center;"><span class="badge pending">{{ $requestStats['pendingAll'] }}</span></td>
                    <td style="text-align:center;"><span class="badge" style="background:#dcfce7;color:#166534;">{{ $requestStats['approvedAll'] }}</span></td>
                    <td style="text-align:center;"><span class="badge" style="background:#fee2e2;color:#dc2626;">{{ $requestStats['rejectedAll'] }}</span></td>
                </tr>
                <tr><td>Tăng ca</td><td style="text-align:center;">{{ $requestStats['totalOvertime'] }}</td><td colspan="3"></td></tr>
                <tr><td>Ứng lương</td><td style="text-align:center;">{{ $requestStats['totalAdvance'] }}</td><td colspan="3"></td></tr>
                <tr><td>Hỗ trợ</td><td style="text-align:center;">{{ $requestStats['totalSupport'] }}</td><td colspan="3"></td></tr>
                <tr style="font-weight:700;background:#f8fafc;">
                    <td>Tổng</td>
                    <td style="text-align:center;">{{ $requestStats['totalLeave'] + $requestStats['totalOvertime'] + $requestStats['totalAdvance'] + $requestStats['totalSupport'] }}</td>
                    <td style="text-align:center;">{{ $requestStats['pendingAll'] }}</td>
                    <td style="text-align:center;">{{ $requestStats['approvedAll'] }}</td>
                    <td style="text-align:center;">{{ $requestStats['rejectedAll'] }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="card" style="padding:18px;">
        <div style="font-weight:700;margin-bottom:14px;">✅ Tổng quan đơn từ</div>
        <canvas id="requestChart" height="220"></canvas>
    </div>
</div>

{{-- 7. Tài khoản --}}
<div class="card" style="margin-bottom:20px;padding:18px;">
    <div style="font-weight:700;margin-bottom:14px;">👤 Thống kê tài khoản</div>
    <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:12px;text-align:center;">
        @foreach([
            ['Tổng', $accountStats['total'], '#2563eb'],
            ['Admin', $accountStats['admin'], '#dc2626'],
            ['Giám đốc', $accountStats['director'] ?? 0, '#7c3aed'],
            ['HR', $accountStats['hr'], '#0ea5e9'],
            ['Kế toán', $accountStats['accountant'], '#d97706'],
            ['Nhân viên', $accountStats['employee'], '#64748b'],
            ['Hoạt động', $accountStats['active'], '#16a34a'],
        ] as [$label, $val, $color])
        <div>
            <div style="font-size:26px;font-weight:800;color:{{ $color }};">{{ $val }}</div>
            <div style="font-size:12px;color:#64748b;">{{ $label }}</div>
        </div>
        @endforeach
    </div>
</div>

{{-- 8. Nhân viên mới theo tháng --}}
<div class="card" style="margin-bottom:20px;padding:18px;">
    <div style="font-weight:700;margin-bottom:14px;">👥 Nhân viên mới theo tháng (12 tháng)</div>
    <canvas id="newEmployeeChart" height="80"></canvas>
</div>

{{-- 9. HĐ sắp hết hạn --}}
@if($expiringContracts->count())
<div class="card" style="margin-bottom:20px;padding:0;overflow:hidden;border-color:#fca5a5;">
    <div style="padding:14px 18px;font-weight:700;background:#fff7ed;border-bottom:1px solid #fca5a5;">
        ⚠️ Hợp đồng sắp hết hạn (30 ngày tới) — {{ $expiringContracts->count() }} hợp đồng
    </div>
    <table>
        <thead>
            <tr>
                <th>Nhân viên</th>
                <th>Phòng ban</th>
                <th>Loại HĐ</th>
                <th>Ngày hết hạn</th>
                <th style="text-align:center;">Còn lại</th>
            </tr>
        </thead>
        <tbody>
            @foreach($expiringContracts as $c)
            @php $daysLeft = now()->diffInDays($c->end_date, false); @endphp
            <tr>
                <td style="font-weight:600;">{{ optional($c->employee)->name ?? 'N/A' }}</td>
                <td>{{ optional(optional($c->employee)->department)->name ?? '—' }}</td>
                <td>{{ $c->contract_type }}</td>
                <td>{{ optional($c->end_date)->format('d/m/Y') }}</td>
                <td style="text-align:center;">
                    <span class="badge" style="background:{{ $daysLeft<=7?'#fee2e2':($daysLeft<=14?'#fef3c7':'#f1f5f9') }};color:{{ $daysLeft<=7?'#dc2626':($daysLeft<=14?'#92400e':'#475569') }};">
                        {{ $daysLeft }} ngày
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

</div>

@push('scripts')
@php
    $trendLabels  = $monthlyPayrollTrend->pluck('label')->toArray();
    $trendData    = $monthlyPayrollTrend->pluck('total')->map(fn($v) => round($v/1000000,1))->toArray();
    $deptPayLabels= $payrollStats['departmentPayroll']->pluck('department_name')->toArray();
    $deptPayData  = $payrollStats['departmentPayroll']->pluck('total_net')->map(fn($v) => round($v/1000000,1))->toArray();
    $ctLabels     = $contractStats['byType']->keys()->map(fn($k) => match($k) {
        'internship'=>'Thực tập','probation'=>'Thử việc','indefinite'=>'Chính thức',
        'fixed_term'=>'XĐ TH','official'=>'LĐ chính thức','seasonal'=>'Thời vụ',default=>ucfirst($k)
    })->toArray();
    $ctData       = $contractStats['byType']->values()->toArray();
    $newEmpLabels = $monthlyNewEmployees->pluck('label')->toArray();
    $newEmpData   = $monthlyNewEmployees->pluck('count')->toArray();
@endphp
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const clr = ['#2563eb','#16a34a','#f59e0b','#dc2626','#7c3aed','#0ea5e9','#ec4899','#14b8a6','#f97316','#6366f1'];

    new Chart(document.getElementById('deptPieChart'), {
        type: 'doughnut',
        data: { labels: @json($departmentStats['departments']->pluck('name')), datasets: [{ data: @json($departmentStats['departments']->pluck('count')), backgroundColor: clr, borderWidth: 2, borderColor:'#fff' }] },
        options: { responsive:true, plugins:{ legend:{ position:'right', labels:{ boxWidth:12, padding:8, font:{size:11} } } } }
    });

    new Chart(document.getElementById('deptBarChart'), {
        type: 'bar',
        data: { labels: @json($departmentStats['departments']->pluck('name')), datasets: [{ label:'Nhân viên', data: @json($departmentStats['departments']->pluck('count')), backgroundColor:'#2563eb', borderRadius:6 }] },
        options: { responsive:true, indexAxis:'y', plugins:{ legend:{display:false} }, scales:{ x:{ beginAtZero:true, ticks:{stepSize:1} } } }
    });

    new Chart(document.getElementById('payrollTrendChart'), {
        type: 'line',
        data: { labels: @json($trendLabels), datasets: [{ label:'Quỹ lương (triệu VNĐ)', data: @json($trendData), borderColor:'#2563eb', backgroundColor:'rgba(37,99,235,0.08)', fill:true, tension:0.3, pointRadius:4 }] },
        options: { responsive:true, plugins:{ legend:{display:false} }, scales:{ y:{ beginAtZero:true, title:{display:true,text:'Triệu VNĐ'} } } }
    });

    new Chart(document.getElementById('payrollDeptChart'), {
        type: 'bar',
        data: { labels: @json($deptPayLabels), datasets: [{ label:'Tổng lương (triệu)', data: @json($deptPayData), backgroundColor: clr, borderRadius:6 }] },
        options: { responsive:true, plugins:{ legend:{display:false} }, scales:{ y:{ beginAtZero:true, title:{display:true,text:'Triệu VNĐ'} } } }
    });

    new Chart(document.getElementById('requestChart'), {
        type: 'doughnut',
        data: { labels:['Chờ duyệt','Đã duyệt','Từ chối'], datasets: [{ data:[{{ $requestStats['pendingAll'] }},{{ $requestStats['approvedAll'] }},{{ $requestStats['rejectedAll'] }}], backgroundColor:['#f59e0b','#16a34a','#dc2626'], borderWidth:2, borderColor:'#fff' }] },
        options: { responsive:true, plugins:{ legend:{ position:'bottom', labels:{boxWidth:12} } } }
    });

    new Chart(document.getElementById('newEmployeeChart'), {
        type: 'bar',
        data: { labels: @json($newEmpLabels), datasets: [{ label:'Nhân viên mới', data: @json($newEmpData), backgroundColor:'#16a34a', borderRadius:6 }] },
        options: { responsive:true, plugins:{ legend:{display:false} }, scales:{ y:{ beginAtZero:true, ticks:{stepSize:1} } } }
    });
});
</script>

<style>
@media print {
    .sidebar, .topbar, .actions { display:none!important; }
    .shell { grid-template-columns:1fr!important; }
    .card { break-inside:avoid; box-shadow:none!important; }
}
</style>
@endpush
@endsection
