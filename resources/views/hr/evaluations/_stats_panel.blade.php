{{-- Partial: dữ liệu thực tế tháng — dùng chung cho form (edit) và show --}}
@php
    $att = $stats['attendance'];
    $lv  = $stats['leave'];
    $ot  = $stats['overtime'];
    $pay = $stats['payroll'];
    $clsMap = ['Xuất sắc'=>'#16a34a','Tốt'=>'#2563eb','Trung bình'=>'#d97706','Yếu'=>'#dc2626'];
    $clsColor = $clsMap[$suggested['classification'] ?? ''] ?? '#64748b';
@endphp
<div style="display:flex;flex-direction:column;gap:14px;">

{{-- Chấm công --}}
<div class="card" style="padding:16px;">
    <div style="font-weight:700;margin-bottom:10px;font-size:14px;">📅 Chấm công tháng</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px;">
        <div style="background:#f0fdf4;border-radius:8px;padding:10px;text-align:center;">
            <div style="font-size:22px;font-weight:800;color:#16a34a;">{{ $att['present_days'] }}</div>
            <div style="font-size:12px;color:#64748b;">Đúng giờ</div>
        </div>
        <div style="background:#fef3c7;border-radius:8px;padding:10px;text-align:center;">
            <div style="font-size:22px;font-weight:800;color:#d97706;">{{ $att['late_days'] }}</div>
            <div style="font-size:12px;color:#64748b;">Đi muộn</div>
        </div>
        <div style="background:#fee2e2;border-radius:8px;padding:10px;text-align:center;">
            <div style="font-size:22px;font-weight:800;color:#dc2626;">{{ $att['absent_days'] }}</div>
            <div style="font-size:12px;color:#64748b;">Vắng mặt</div>
        </div>
        <div style="background:#ede9fe;border-radius:8px;padding:10px;text-align:center;">
            <div style="font-size:22px;font-weight:800;color:#7c3aed;">{{ $att['overtime_days'] }}</div>
            <div style="font-size:12px;color:#64748b;">Có OT</div>
        </div>
    </div>
    <div style="font-size:13px;color:#475569;display:flex;flex-wrap:wrap;gap:8px;">
        <span>⏱ TB <strong>{{ $att['avg_work_hours'] }}h/ngày</strong></span>
        <span>·</span>
        <span>⏰ Muộn tổng <strong>{{ $att['total_late_min'] }} phút</strong></span>
        <span>·</span>
        <span>📋 {{ $att['total_records'] }} bản ghi</span>
    </div>

    @if(count($att['details']) > 0)
    <details style="margin-top:10px;">
        <summary style="cursor:pointer;font-size:13px;color:#2563eb;font-weight:600;user-select:none;">
            Xem chi tiết từng ngày ({{ count($att['details']) }})
        </summary>
        <div style="margin-top:8px;max-height:200px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:8px;">
            <table style="width:100%;font-size:12px;border-collapse:collapse;">
                <thead style="position:sticky;top:0;background:#f8fafc;">
                    <tr style="color:#64748b;">
                        <th style="padding:6px 8px;text-align:left;">Ngày</th>
                        <th style="padding:6px 8px;text-align:left;">Trạng thái</th>
                        <th style="padding:6px 8px;text-align:left;">Vào</th>
                        <th style="padding:6px 8px;text-align:left;">Ra</th>
                        <th style="padding:6px 8px;text-align:right;">Giờ làm</th>
                        <th style="padding:6px 8px;text-align:right;">Muộn</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($att['details'] as $d)
                @php
                    $rowBg = match($d['status']) {
                        'present'                         => '#f0fdf4',
                        'late','late_and_leave_early'     => '#fefce8',
                        'absent'                          => '#fff1f2',
                        default                           => 'transparent',
                    };
                @endphp
                <tr style="background:{{ $rowBg }};border-bottom:1px solid #f1f5f9;">
                    <td style="padding:5px 8px;font-weight:600;">{{ $d['date'] }}</td>
                    <td style="padding:5px 8px;">{{ $d['status_label'] }}</td>
                    <td style="padding:5px 8px;">{{ $d['check_in'] ?? '—' }}</td>
                    <td style="padding:5px 8px;">{{ $d['check_out'] ?? '—' }}</td>
                    <td style="padding:5px 8px;text-align:right;">{{ $d['work_hours'] ? number_format($d['work_hours'],1).'h' : '—' }}</td>
                    <td style="padding:5px 8px;text-align:right;">{{ $d['late_minutes'] > 0 ? $d['late_minutes'].'p' : '—' }}</td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </details>
    @endif
</div>

{{-- Nghỉ phép --}}
<div class="card" style="padding:16px;">
    <div style="font-weight:700;margin-bottom:10px;font-size:14px;">🏖 Nghỉ phép tháng</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px;">
        <span style="padding:3px 9px;border-radius:999px;font-size:12px;font-weight:700;background:#dcfce7;color:#166534;">
            ✅ Duyệt: {{ $lv['approved_count'] }} đơn ({{ $lv['approved_days'] }} ngày)
        </span>
        @if($lv['pending_count'] > 0)
        <span style="padding:3px 9px;border-radius:999px;font-size:12px;font-weight:700;background:#fef9c3;color:#854d0e;">
            ⏳ Chờ: {{ $lv['pending_count'] }}
        </span>
        @endif
        @if($lv['urgent_count'] > 0)
        <span style="padding:3px 9px;border-radius:999px;font-size:12px;font-weight:700;background:#fee2e2;color:#dc2626;">
            ⚡ Đột xuất: {{ $lv['urgent_count'] }}
        </span>
        @endif
        @if($lv['total_count'] === 0)
        <span style="font-size:13px;color:#94a3b8;">Không có đơn nghỉ phép</span>
        @endif
    </div>
    @foreach(array_slice($lv['list'], 0, 5) as $l)
    @php $lc = $l['status']==='approved'?'#dcfce7':($l['status']==='pending'?'#fef9c3':'#fee2e2'); @endphp
    <div style="font-size:12px;padding:5px 0;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;">
        <span style="color:#475569;">{{ $l['start'] }}→{{ $l['end'] }} · {{ $l['days'] }} ngày
            @if($l['type']) · {{ $l['type'] }} @endif
            @if($l['urgent']) <span style="color:#dc2626;">⚡</span> @endif
        </span>
        <span style="background:{{ $lc }};padding:1px 8px;border-radius:999px;font-size:11px;font-weight:700;">{{ $l['status'] }}</span>
    </div>
    @endforeach
</div>

{{-- Tăng ca --}}
<div class="card" style="padding:16px;">
    <div style="font-weight:700;margin-bottom:10px;font-size:14px;">⏰ Tăng ca</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <span style="padding:3px 9px;border-radius:999px;font-size:12px;font-weight:700;background:#dcfce7;color:#166534;">
            ✅ Duyệt: {{ $ot['approved_count'] }}
        </span>
        @if($ot['pending_count'] > 0)
        <span style="padding:3px 9px;border-radius:999px;font-size:12px;font-weight:700;background:#fef9c3;color:#854d0e;">
            ⏳ Chờ: {{ $ot['pending_count'] }}
        </span>
        @endif
        @if($ot['total_count'] === 0)
        <span style="font-size:13px;color:#94a3b8;">Không có yêu cầu tăng ca</span>
        @endif
    </div>
    @foreach($ot['list'] as $o)
    <div style="font-size:12px;padding:5px 0;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;">
        <span style="color:#475569;">{{ $o['date'] }} · {{ $o['start'] }}→{{ $o['end'] }}</span>
        @php $oc = $o['status']==='approved'?'#dcfce7':($o['status']==='pending'?'#fef9c3':'#fee2e2'); @endphp
        <span style="background:{{ $oc }};padding:1px 8px;border-radius:999px;font-size:11px;font-weight:700;">{{ $o['status'] }}</span>
    </div>
    @endforeach
</div>

{{-- Lương --}}
@if(!empty($pay['exists']) && $pay['exists'])
<div class="card" style="padding:16px;">
    <div style="font-weight:700;margin-bottom:10px;font-size:14px;">💰 Lương tháng này</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px;font-size:13px;">
        <span style="color:#64748b;">Lương cơ bản</span>
        <span style="font-weight:600;text-align:right;">{{ number_format($pay['base_salary'],0,',','.') }}₫</span>
        <span style="color:#64748b;">Phụ cấp</span>
        <span style="font-weight:600;text-align:right;">{{ number_format($pay['allowance'],0,',','.') }}₫</span>
        <span style="color:#64748b;">Thưởng</span>
        <span style="font-weight:600;text-align:right;">{{ number_format($pay['bonus'],0,',','.') }}₫</span>
        <span style="color:#dc2626;">Khấu trừ</span>
        <span style="font-weight:600;text-align:right;color:#dc2626;">{{ number_format($pay['deduction'],0,',','.') }}₫</span>
        <span style="border-top:1px solid #e2e8f0;padding-top:6px;color:#16a34a;font-weight:700;">Thực lĩnh</span>
        <span style="border-top:1px solid #e2e8f0;padding-top:6px;font-weight:800;text-align:right;color:#16a34a;">{{ number_format($pay['total_salary'],0,',','.') }}₫</span>
    </div>
</div>
@endif

{{-- Đề xuất (chỉ hiện khi có) --}}
@if(!empty($suggested))
<div class="card" style="padding:16px;border:2px solid {{ $clsColor }}44;background:{{ $clsColor }}0d;">
    <div style="font-weight:700;margin-bottom:12px;font-size:14px;">✨ Đề xuất hệ thống</div>
    <div style="display:flex;align-items:center;gap:14px;margin-bottom:12px;">
        <div style="font-size:40px;font-weight:900;color:{{ $clsColor }};line-height:1;">{{ $suggested['score_total'] }}</div>
        <div>
            <div style="font-size:11px;color:#64748b;">/ 100 điểm</div>
            <span style="padding:4px 12px;border-radius:999px;font-size:13px;font-weight:700;color:#fff;background:{{ $clsColor }};">{{ $suggested['classification'] }}</span>
        </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px;font-size:12px;color:#475569;">
        <span>Đúng giờ: <strong>{{ $suggested['punctuality'] }}/10</strong></span>
        <span>Hoàn thành: <strong>{{ $suggested['task_completion'] }}/30</strong></span>
        <span>Chất lượng: <strong>{{ $suggested['quality'] }}/20</strong></span>
        <span>Chuyên môn: <strong>{{ $suggested['technical_skill'] }}/10</strong></span>
        <span>Trách nhiệm: <strong>{{ $suggested['responsibility'] }}/10</strong></span>
        <span>Nhóm: <strong>{{ $suggested['teamwork'] }}/10</strong></span>
        <span>Thái độ: <strong>{{ $suggested['attitude'] }}/10</strong></span>
    </div>
</div>
@endif

</div>
