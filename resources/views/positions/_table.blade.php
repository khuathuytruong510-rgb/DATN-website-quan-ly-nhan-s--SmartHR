@isset($title)
    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:8px;">
        <h2 style="font-size:17px; margin:0;">
            {{ $title }}
            @isset($deptLink)
            @endisset
        </h2>
        <span class="badge bg-secondary">{{ $positions->count() }} chức vụ</span>
    </div>
@endisset

<div class="table-responsive">
    <table class="table" style="width:100%; border-collapse:collapse;">
        <thead>
            <tr>
                <th style="padding:10px 12px; text-align:left; border-bottom:1px solid #e5e7eb;">STT</th>
                <th style="padding:10px 12px; text-align:left; border-bottom:1px solid #e5e7eb;">Tên chức vụ</th>
                <th style="padding:10px 12px; text-align:left; border-bottom:1px solid #e5e7eb;">Cấp bậc</th>
                <th style="padding:10px 12px; text-align:left; border-bottom:1px solid #e5e7eb;">Lương cơ bản</th>
                <th style="padding:10px 12px; text-align:left; border-bottom:1px solid #e5e7eb;">Khoảng lương</th>
                <th style="padding:10px 12px; text-align:left; border-bottom:1px solid #e5e7eb;">Nhân viên</th>
                <th style="padding:10px 12px; text-align:left; border-bottom:1px solid #e5e7eb;">Mô tả</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($positions as $p)
                <tr>
                    <td style="padding:10px 12px; border-bottom:1px solid #e5e7eb;">{{ $loop->iteration }}</td>
                    <td style="padding:10px 12px; border-bottom:1px solid #e5e7eb;">
                        <strong>{{ $p->name }}</strong>
                        @if ($p->department && ! isset($title))
                            <br><span class="muted" style="font-size:12px;">{{ $p->department->name }}</span>
                        @endif
                    </td>
                    <td style="padding:10px 12px; border-bottom:1px solid #e5e7eb;"><span class="badge bg-secondary">{{ $p->level }}</span></td>
                    <td style="padding:10px 12px; border-bottom:1px solid #e5e7eb;">{{ number_format($p->base_salary, 0, ',', '.') }} đ</td>
                    <td style="padding:10px 12px; border-bottom:1px solid #e5e7eb;">{{ number_format($p->salary_range_min, 0, ',', '.') }} – {{ number_format($p->salary_range_max, 0, ',', '.') }} đ</td>
                    <td style="padding:10px 12px; border-bottom:1px solid #e5e7eb;">{{ $p->employees->count() }}</td>
                    <td style="padding:10px 12px; border-bottom:1px solid #e5e7eb;">{{ $p->description ?: '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>