@extends('layouts.app')
@section('title', 'Chi tiết đánh giá — ' . optional($evaluation->employee)->name)

@section('content')
<div style="max-width:1100px;">

<div class="page-head">
    <div>
        <h1>Chi tiết đánh giá</h1>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('evaluations.index') }}">← Quay lại</a>
        <a class="btn primary" href="{{ route('evaluations.edit', $evaluation) }}">✏ Sửa</a>
        @if($evaluation->status === 'pending')
        <form method="POST" action="{{ route('evaluations.approve', $evaluation) }}" style="display:inline;">
            @csrf
            <button type="submit" class="btn" style="background:#dcfce7;color:#16a34a;">✅ Duyệt</button>
        </form>
        @endif
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 380px;gap:20px;align-items:start;">

{{-- LEFT: kết quả đánh giá --}}
<div style="display:flex;flex-direction:column;gap:16px;">

    {{-- Nhân viên --}}
    <div class="card" style="padding:20px;">
        @php
            $statusColors = ['pending'=>'background:#fef9c3;color:#854d0e','approved'=>'background:#dcfce7;color:#166534','rejected'=>'background:#fee2e2;color:#dc2626'];
            $statusLabels = ['pending'=>'Chờ duyệt','approved'=>'Đã duyệt','rejected'=>'Từ chối'];
        @endphp
        <div style="display:flex;align-items:center;gap:16px;">
            <div style="width:52px;height:52px;border-radius:50%;background:#dbeafe;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:800;color:#2563eb;flex-shrink:0;">
                {{ mb_substr(optional($evaluation->employee)->name ?? '?', 0, 1) }}
            </div>
            <div style="flex:1;">
                <div style="font-size:18px;font-weight:700;">{{ optional($evaluation->employee)->name }}</div>
                <div style="color:#64748b;font-size:14px;">
                    {{ optional($evaluation->employee->department)->name ?? '—' }} ·
                    {{ optional($evaluation->employee)->position ?? '—' }}
                </div>
            </div>
            <div style="text-align:right;">
                <span style="padding:5px 12px;border-radius:999px;font-size:13px;font-weight:700;{{ $statusColors[$evaluation->status] ?? 'background:#f1f5f9;color:#475569' }};">
                    {{ $statusLabels[$evaluation->status] ?? $evaluation->status }}
                </span>
                @if($evaluation->approved_at)
                <div style="font-size:12px;color:#64748b;margin-top:4px;">
                    Duyệt lúc {{ $evaluation->approved_at->format('H:i d/m/Y') }}<br>
                    bởi {{ optional($evaluation->approvedBy)->name }}
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Tổng điểm --}}
    @php
        $clsColor = ['Xuất sắc'=>'#16a34a','Tốt'=>'#2563eb','Trung bình'=>'#d97706','Yếu'=>'#dc2626'][$evaluation->classification] ?? '#64748b';
    @endphp
    <div class="card" style="padding:20px;">
        <div style="display:flex;align-items:center;gap:16px;margin-bottom:14px;">
            <div style="font-size:60px;font-weight:900;color:{{ $clsColor }};line-height:1;">{{ $evaluation->score_total }}</div>
            <div>
                <div style="font-size:14px;color:#64748b;">điểm / 100</div>
                <span style="padding:5px 14px;border-radius:999px;font-size:14px;font-weight:700;color:#fff;background:{{ $clsColor }};">{{ $evaluation->classification }}</span>
                <div style="font-size:13px;color:#64748b;margin-top:4px;">Rating {{ $evaluation->rating }}/5 ⭐</div>
            </div>
        </div>
        <div style="background:#f1f5f9;border-radius:999px;height:10px;overflow:hidden;">
            <div style="width:{{ $evaluation->score_total }}%;background:{{ $clsColor }};height:100%;border-radius:999px;"></div>
        </div>
    </div>

    {{-- Tiêu chí chi tiết --}}
    <div class="card" style="padding:20px;">
        <h3 style="margin:0 0 14px;font-size:15px;">Tiêu chí chi tiết</h3>
        @php
        $criteria = [
            ['key'=>'punctuality',    'label'=>'Đi đúng giờ',         'max'=>10, 'color'=>'#0ea5e9'],
            ['key'=>'task_completion','label'=>'Hoàn thành công việc', 'max'=>30, 'color'=>'#8b5cf6'],
            ['key'=>'quality',        'label'=>'Chất lượng công việc', 'max'=>20, 'color'=>'#10b981'],
            ['key'=>'technical_skill','label'=>'Kỹ năng chuyên môn',   'max'=>10, 'color'=>'#f59e0b'],
            ['key'=>'responsibility', 'label'=>'Trách nhiệm',          'max'=>10, 'color'=>'#ef4444'],
            ['key'=>'teamwork',       'label'=>'Làm việc nhóm',        'max'=>10, 'color'=>'#ec4899'],
            ['key'=>'attitude',       'label'=>'Thái độ',              'max'=>10, 'color'=>'#14b8a6'],
        ];
        @endphp
        @foreach($criteria as $c)
        @php $val = $evaluation->{$c['key']} ?? 0; $pct = $c['max']>0 ? round($val/$c['max']*100) : 0; @endphp
        <div style="display:grid;grid-template-columns:140px 1fr 60px;align-items:center;gap:10px;margin-bottom:10px;">
            <span style="font-size:13px;color:#475569;">{{ $c['label'] }}</span>
            <div style="background:#f1f5f9;border-radius:999px;height:8px;overflow:hidden;">
                <div style="width:{{ $pct }}%;background:{{ $c['color'] }};height:100%;border-radius:999px;"></div>
            </div>
            <span style="font-size:13px;font-weight:700;text-align:right;">{{ $val }}<span style="color:#94a3b8;font-weight:400;">/{{ $c['max'] }}</span></span>
        </div>
        @endforeach
    </div>

    {{-- Nhận xét --}}
    @if($evaluation->summary || $evaluation->comments)
    <div class="card" style="padding:20px;">
        @if($evaluation->summary)
        <div style="margin-bottom:14px;">
            <div style="font-weight:700;margin-bottom:6px;font-size:14px;">📝 Tóm tắt</div>
            <p style="margin:0;color:#475569;line-height:1.7;">{{ $evaluation->summary }}</p>
        </div>
        @endif
        @if($evaluation->comments)
        <div>
            <div style="font-weight:700;margin-bottom:6px;font-size:14px;">💬 Nhận xét</div>
            <p style="margin:0;color:#475569;line-height:1.7;">{{ $evaluation->comments }}</p>
        </div>
        @endif
    </div>
    @endif

    <div style="font-size:13px;color:#94a3b8;padding:4px 0;">
        Người đánh giá: {{ optional($evaluation->evaluator)->name ?? 'Hệ thống' }} ·
        Cập nhật: {{ $evaluation->updated_at->format('H:i d/m/Y') }}
    </div>
</div>

{{-- RIGHT: dữ liệu thực tế --}}
<div>
    <div style="font-weight:700;margin-bottom:10px;font-size:13px;color:#64748b;text-transform:uppercase;letter-spacing:.05em;">
        Dữ liệu thực tế tháng {{ $evaluation->month }}
    </div>
    @include('hr.evaluations._stats_panel', ['stats' => $monthlyStats, 'suggested' => null])
</div>

</div>{{-- /grid --}}
</div>{{-- /max-width --}}
@endsection
