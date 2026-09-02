@extends('layouts.app')
@section('title', 'Đánh giá nhân viên')

@section('content')
<div style="max-width:100%;">

<div class="page-head">
    <div>
        <h1>Đánh giá nhân viên</h1>
        <p class="muted">Quản lý và theo dõi hiệu suất nhân viên theo tháng</p>
    </div>
    <a href="{{ route('evaluations.create') }}" class="btn primary">+ Tạo đánh giá</a>
</div>

@if(session('success'))
<div class="alert" style="background:#dcfce7;color:#166534;margin-bottom:16px;">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert" style="background:#fee2e2;color:#dc2626;margin-bottom:16px;">{{ session('error') }}</div>
@endif

{{-- THỐNG KÊ --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:16px;">
    <div class="card" style="padding:16px;text-align:center;">
        <div style="font-size:28px;font-weight:900;color:#2563eb;">{{ $stats['total'] }}</div>
        <div style="font-size:13px;color:#64748b;">Tổng đánh giá</div>
    </div>
    <div class="card" style="padding:16px;text-align:center;">
        <div style="font-size:28px;font-weight:900;color:#d97706;">{{ $stats['pending'] }}</div>
        <div style="font-size:13px;color:#64748b;">Chờ duyệt</div>
    </div>
    <div class="card" style="padding:16px;text-align:center;">
        <div style="font-size:28px;font-weight:900;color:#16a34a;">{{ $stats['approved'] }}</div>
        <div style="font-size:13px;color:#64748b;">Đã duyệt</div>
    </div>
    <div class="card" style="padding:16px;text-align:center;">
        <div style="font-size:28px;font-weight:900;color:#7c3aed;">{{ $stats['avg_score'] }}</div>
        <div style="font-size:13px;color:#64748b;">Điểm trung bình</div>
    </div>
</div>

{{-- Phân loại nhanh (giữ các filter hiện tại khi click) --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:18px;">
    @php
    $cls = [
        ['Xuất sắc', $stats['excellent'], '#f0fdf4', '#16a34a'],
        ['Tốt',      $stats['good'],      '#dbeafe', '#2563eb'],
        ['Trung bình',$stats['average'],  '#fef9c3', '#d97706'],
        ['Yếu',      $stats['weak'],      '#fee2e2', '#dc2626'],
    ];
    @endphp
    @foreach($cls as [$label, $count, $bg, $color])
    @php
        $clsUrl = route('evaluations.index', array_filter([
            'search'         => $search,
            'month'          => $filterMonth,
            'status'         => $filterStatus,
            'classification' => $filterClass === $label ? '' : $label,
        ]));
        $isActive = $filterClass === $label;
    @endphp
    <a href="{{ $clsUrl }}" style="text-decoration:none;">
        <div style="background:{{ $bg }};border-radius:10px;padding:12px 16px;display:flex;align-items:center;gap:10px;border:{{ $isActive ? '2px' : '1px' }} solid {{ $color }}{{ $isActive ? '' : '44' }};">
            <div style="font-size:24px;font-weight:800;color:{{ $color }};">{{ $count }}</div>
            <div style="font-size:13px;font-weight:600;color:{{ $color }};">{{ $label }}{{ $isActive ? ' ✓' : '' }}</div>
        </div>
    </a>
    @endforeach
</div>

{{-- FILTER --}}
<form method="GET" action="{{ route('evaluations.index') }}"
      style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;margin-bottom:18px;background:#f8fafc;padding:14px 16px;border-radius:10px;border:1px solid #e2e8f0;">
    <div>
        <label style="display:block;font-size:12px;color:#64748b;margin-bottom:4px;">Tên nhân viên</label>
        <input type="text" name="search" value="{{ $search }}" placeholder="Tìm kiếm..."
               style="padding:8px 12px;border:1px solid #cbd5e1;border-radius:8px;font:inherit;width:180px;">
    </div>
    <div>
        <label style="display:block;font-size:12px;color:#64748b;margin-bottom:4px;">Tháng</label>
        <input type="month" name="month" value="{{ $filterMonth }}"
               style="padding:8px 12px;border:1px solid #cbd5e1;border-radius:8px;font:inherit;">
    </div>
    <div>
        <label style="display:block;font-size:12px;color:#64748b;margin-bottom:4px;">Trạng thái</label>
        <select name="status" style="padding:8px 12px;border:1px solid #cbd5e1;border-radius:8px;font:inherit;">
            <option value="">Tất cả</option>
            <option value="pending"  {{ $filterStatus === 'pending'  ? 'selected' : '' }}>Chờ duyệt</option>
            <option value="approved" {{ $filterStatus === 'approved' ? 'selected' : '' }}>Đã duyệt</option>
            <option value="rejected" {{ $filterStatus === 'rejected' ? 'selected' : '' }}>Từ chối</option>
        </select>
    </div>
    <div>
        <label style="display:block;font-size:12px;color:#64748b;margin-bottom:4px;">Phân loại</label>
        <select name="classification" style="padding:8px 12px;border:1px solid #cbd5e1;border-radius:8px;font:inherit;">
            <option value="">Tất cả</option>
            @foreach(['Xuất sắc','Tốt','Trung bình','Yếu'] as $c)
            <option value="{{ $c }}" {{ $filterClass === $c ? 'selected' : '' }}>{{ $c }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="btn primary" style="padding:9px 16px;">Lọc</button>
    @if($search !== '' || $filterMonth !== '' || $filterStatus !== '' || $filterClass !== '')
    <a href="{{ route('evaluations.index') }}" class="btn" style="padding:9px 16px;">✕ Xoá lọc</a>
    @endif
</form>

{{-- Indicator đang lọc --}}
@if($search !== '' || $filterMonth !== '' || $filterStatus !== '' || $filterClass !== '')
<div style="margin-bottom:12px;font-size:13px;color:#64748b;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
    <span>Đang lọc:</span>
    @if($search !== '')     <span style="background:#dbeafe;color:#1d4ed8;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600;">Tên: {{ $search }}</span> @endif
    @if($filterMonth !== '') <span style="background:#dbeafe;color:#1d4ed8;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600;">Tháng: {{ $filterMonth }}</span> @endif
    @if($filterStatus !== '')<span style="background:#dbeafe;color:#1d4ed8;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600;">Trạng thái: {{ $filterStatus }}</span> @endif
    @if($filterClass !== '') <span style="background:#dbeafe;color:#1d4ed8;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600;">Phân loại: {{ $filterClass }}</span> @endif
    <span style="color:#94a3b8;">— {{ $evaluations->total() }} kết quả</span>
</div>
@endif

{{-- BẢNG --}}
@if($evaluations->count())
<div class="card" style="padding:0;overflow:hidden;">
    <table style="width:100%;border-collapse:collapse;">
        <thead style="background:#f8fafc;">
            <tr>
                @foreach(['Nhân viên','Tháng','Điểm','Phân loại','Người đánh giá','Trạng thái','Hành động'] as $h)
                <th style="padding:12px 14px;text-align:left;font-size:12px;color:#64748b;font-weight:700;text-transform:uppercase;border-bottom:1px solid #e2e8f0;">{{ $h }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
        @php
        $clsMap = [
            'Xuất sắc' => ['#f0fdf4','#16a34a'],
            'Tốt'      => ['#dbeafe','#2563eb'],
            'Trung bình'=> ['#fef9c3','#d97706'],
            'Yếu'      => ['#fee2e2','#dc2626'],
        ];
        $stMap = [
            'pending'  => ['#fef9c3','#854d0e','Chờ duyệt'],
            'approved' => ['#dcfce7','#166534','Đã duyệt'],
            'rejected' => ['#fee2e2','#dc2626','Từ chối'],
        ];
        @endphp
        @foreach($evaluations as $ev)
        @php
            [$cb,$cc] = $clsMap[$ev->classification] ?? ['#f1f5f9','#475569'];
            [$sb,$sc,$sl] = $stMap[$ev->status] ?? ['#f1f5f9','#475569', $ev->status];
        @endphp
        <tr style="border-bottom:1px solid #f1f5f9;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
            <td style="padding:12px 14px;">
                <a href="{{ route('evaluations.show', $ev) }}" style="text-decoration:none;color:inherit;">
                    <div style="font-weight:600;">{{ optional($ev->employee)->name ?? '—' }}</div>
                    <div style="font-size:12px;color:#64748b;">{{ optional(optional($ev->employee)->department)->name ?? '—' }}</div>
                </a>
            </td>
            <td style="padding:12px 14px;color:#475569;">{{ $ev->month }}</td>
            <td style="padding:12px 14px;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="font-size:20px;font-weight:800;color:{{ $cc }};">{{ $ev->score_total }}</span>
                    <span style="font-size:12px;color:#94a3b8;">/100</span>
                </div>
                <div style="background:#f1f5f9;border-radius:999px;height:4px;width:60px;margin-top:4px;overflow:hidden;">
                    <div style="width:{{ $ev->score_total }}%;background:{{ $cc }};height:100%;border-radius:999px;"></div>
                </div>
            </td>
            <td style="padding:12px 14px;">
                <span style="padding:4px 10px;border-radius:999px;font-size:12px;font-weight:700;background:{{ $cb }};color:{{ $cc }};">{{ $ev->classification ?? '—' }}</span>
            </td>
            <td style="padding:12px 14px;font-size:13px;color:#475569;">
                {{ optional($ev->evaluator)->name ?? 'Hệ thống' }}
            </td>
            <td style="padding:12px 14px;">
                <span style="padding:4px 10px;border-radius:999px;font-size:12px;font-weight:700;background:{{ $sb }};color:{{ $sc }};">{{ $sl }}</span>
            </td>
            <td style="padding:12px 14px;">
                <div style="display:flex;gap:5px;flex-wrap:wrap;">
                    <a href="{{ route('evaluations.show', $ev) }}" class="btn" style="padding:5px 10px;font-size:12px;">Chi tiết</a>
                    @if(auth()->user()?->is_admin || auth()->user()?->is_hr)
                    <a href="{{ route('evaluations.edit', $ev) }}" class="btn" style="padding:5px 10px;font-size:12px;">Sửa</a>
                    @if($ev->status === 'pending')
                    <form method="POST" action="{{ route('evaluations.approve', $ev) }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="btn" style="padding:5px 10px;font-size:12px;background:#dcfce7;color:#16a34a;">Duyệt</button>
                    </form>
                    @endif
                    <form method="POST" action="{{ route('evaluations.destroy', $ev) }}" style="display:inline;"
                          data-confirm="Xóa đánh giá tháng {{ $ev->month }} của {{ optional($ev->employee)->name }}?">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn danger" style="padding:5px 10px;font-size:12px;">Xóa</button>
                    </form>
                    @endif
                </div>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div style="margin-top:16px;">{{ $evaluations->links() }}</div>
@else
<div class="card" style="padding:48px;text-align:center;color:#94a3b8;">
    <div style="font-size:44px;margin-bottom:10px;">📋</div>
    @if($search !== '' || $filterMonth !== '' || $filterStatus !== '' || $filterClass !== '')
    <p style="margin:0;">Không có đánh giá nào khớp với bộ lọc.
        <a href="{{ route('evaluations.index') }}" style="color:#2563eb;font-weight:700;">Xoá bộ lọc</a>
    </p>
    @else
    <p style="margin:0;">Chưa có đánh giá nào.
        <a href="{{ route('evaluations.create') }}" style="color:#2563eb;font-weight:700;">Tạo mới ngay</a>
    </p>
    @endif
</div>
@endif

</div>
@endsection
