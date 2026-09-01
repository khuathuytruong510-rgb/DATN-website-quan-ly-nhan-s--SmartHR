<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bảng lương tháng {{ sprintf('%02d/%d', $month, $year) }} — Trình duyệt</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Times New Roman", Times, serif;
            color: #111;
            background: #e5e7eb;
            font-size: 13px;
        }
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: space-between;
            padding: 12px 18px;
            background: #111827;
            color: #fff;
        }
        .toolbar a, .toolbar button {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 0;
            border-radius: 8px;
            padding: 8px 14px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
        }
        .btn-print { background: #2563eb; color: #fff; }
        .btn-back { background: #e5e7eb; color: #111; }
        .sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 18px auto;
            background: #fff;
            padding: 18mm 14mm;
            box-shadow: 0 10px 30px rgba(0,0,0,.12);
        }
        .company {
            text-align: center;
            margin-bottom: 8px;
        }
        .company h1 {
            margin: 0;
            font-size: 20px;
            letter-spacing: .5px;
            text-transform: uppercase;
        }
        .company p { margin: 4px 0 0; font-size: 12px; }
        .doc-title {
            text-align: center;
            margin: 18px 0 6px;
            font-size: 18px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .meta {
            text-align: center;
            margin-bottom: 16px;
            font-size: 13px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #333;
            padding: 5px 4px;
            vertical-align: middle;
            word-wrap: break-word;
        }
        th {
            background: #f3f4f6;
            font-size: 11px;
            text-transform: uppercase;
        }
        td { font-size: 12px; }
        .num { text-align: right; font-variant-numeric: tabular-nums; }
        .center { text-align: center; }
        .totals td { font-weight: 700; background: #fafafa; }
        .note {
            margin-top: 14px;
            font-size: 12px;
            line-height: 1.45;
        }
        .signs {
            margin-top: 28px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px;
            text-align: center;
        }
        .signs .role { font-weight: 700; margin-bottom: 4px; }
        .signs .hint { font-size: 11px; font-style: italic; color: #444; }
        .signs .space { height: 70px; }
        .footer {
            margin-top: 18px;
            font-size: 11px;
            color: #555;
            display: flex;
            justify-content: space-between;
        }
        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .sheet {
                width: auto;
                min-height: auto;
                margin: 0;
                padding: 0;
                box-shadow: none;
            }
            @page { size: A4 landscape; margin: 10mm; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div>
            <strong>Bản in trình Giám đốc phê duyệt</strong>
            <span style="opacity:.8;margin-left:8px;">Tháng {{ sprintf('%02d/%d', $month, $year) }}</span>
        </div>
        <div style="display:flex;gap:8px;">
            <a class="btn-back" href="{{ route('payroll.index', ['month' => $month, 'year' => $year]) }}">← Quay lại</a>
            <button class="btn-print" type="button" onclick="window.print()">In bảng lương</button>
        </div>
    </div>

    <div class="sheet">
        <div class="company">
            <h1>SmartHR</h1>
            <p>Hệ thống quản lý nhân sự</p>
        </div>

        <div class="doc-title">Bảng tổng hợp lương nhân viên</div>
        <div class="meta">
            Kỳ lương: <strong>Tháng {{ sprintf('%02d/%d', $month, $year) }}</strong>
            &nbsp;|&nbsp; Số phiếu: <strong>{{ $payrolls->count() }}</strong>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:4%;">STT</th>
                    <th style="width:16%;">Họ và tên</th>
                    <th style="width:10%;">Phòng ban</th>
                    <th style="width:10%;">Chức vụ</th>
                    <th style="width:9%;">Lương CB</th>
                    <th style="width:7%;">Ngày công</th>
                    <th style="width:9%;">Lương công</th>
                    <th style="width:8%;">Tăng ca</th>
                    <th style="width:7%;">Phụ cấp</th>
                    <th style="width:7%;">Thưởng</th>
                    <th style="width:7%;">BH + Thuế</th>
                    <th style="width:10%;">Thực nhận</th>
                    <th style="width:8%;">Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payrolls as $i => $p)
                    <tr>
                        <td class="center">{{ $i + 1 }}</td>
                        <td>{{ optional($p->employee)->name }}</td>
                        <td>{{ optional(optional($p->employee)->department)->name ?: '—' }}</td>
                        <td>{{ optional($p->employee)->position ?: '—' }}</td>
                        <td class="num">{{ number_format((float) $p->base_salary, 0, ',', '.') }}</td>
                        <td class="center">{{ $p->working_days }}/{{ $p->required_working_days }}</td>
                        <td class="num">{{ number_format((float) $p->working_salary, 0, ',', '.') }}</td>
                        <td class="num">{{ number_format((float) $p->overtime_salary, 0, ',', '.') }}</td>
                        <td class="num">{{ number_format((float) $p->allowance, 0, ',', '.') }}</td>
                        <td class="num">{{ number_format((float) $p->bonus, 0, ',', '.') }}</td>
                        <td class="num">{{ number_format((float) $p->insurance + (float) $p->tax, 0, ',', '.') }}</td>
                        <td class="num"><strong>{{ number_format((float) $p->total_salary, 0, ',', '.') }}</strong></td>
                        <td class="center">{{ $workflow->statusLabel($p->status) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="13" class="center">Chưa có dữ liệu bảng lương tháng này.</td>
                    </tr>
                @endforelse

                @if($payrolls->isNotEmpty())
                    <tr class="totals">
                        <td colspan="4" class="center">TỔNG CỘNG</td>
                        <td class="num">{{ number_format($totals['base_salary'], 0, ',', '.') }}</td>
                        <td></td>
                        <td class="num">{{ number_format($totals['working_salary'], 0, ',', '.') }}</td>
                        <td class="num">{{ number_format($totals['overtime_salary'], 0, ',', '.') }}</td>
                        <td class="num">{{ number_format($totals['allowance'], 0, ',', '.') }}</td>
                        <td class="num">{{ number_format($totals['bonus'], 0, ',', '.') }}</td>
                        <td class="num">{{ number_format($totals['insurance'] + $totals['tax'], 0, ',', '.') }}</td>
                        <td class="num">{{ number_format($totals['total_salary'], 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                @endif
            </tbody>
        </table>

        <div class="note">
            <strong>Ghi chú:</strong> Bảng lương tổng hợp sau khi HR xác nhận nghiệp vụ, dùng để Giám đốc phê duyệt cuối.
            Sau khi phê duyệt cuối, hệ thống phát hành phiếu và gửi xác nhận cho từng nhân viên.
        </div>

        <div class="signs">
            <div>
                <div class="role">Người lập biểu</div>
                <div class="hint">(Ký, họ tên)</div>
                <div class="space"></div>
                <div>{{ optional($printedBy)->name }}</div>
            </div>
            <div>
                <div class="role">Trưởng phòng Nhân sự</div>
                <div class="hint">(Ký, họ tên)</div>
                <div class="space"></div>
            </div>
            <div>
                <div class="role">Giám đốc</div>
                <div class="hint">(Phê duyệt cuối — Ký, họ tên)</div>
                <div class="space"></div>
            </div>
        </div>

        <div class="footer">
            <span>In lúc: {{ $printedAt->format('d/m/Y H:i') }}</span>
            <span>SmartHR — Bảng lương {{ sprintf('%02d/%d', $month, $year) }}</span>
        </div>
    </div>
</body>
</html>
