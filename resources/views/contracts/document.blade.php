<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>{{ $payload['contract_code'] ?? $contract->contract_code }} — Hợp đồng lao động</title>
    <style>
        @page { size: A4; margin: 2cm 2cm 2.5cm 2.5cm; }
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 13pt;
            line-height: 1.45;
            color: #000;
            max-width: 820px;
            margin: 24px auto;
            padding: 0 16px 40px;
        }
        .no-print { margin-bottom: 16px; }
        .no-print button {
            font-family: system-ui, sans-serif;
            font-size: 14px;
            padding: 8px 14px;
            cursor: pointer;
        }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .nation { font-weight: bold; text-transform: uppercase; letter-spacing: .4px; margin: 0; }
        .motto { font-style: italic; margin: 4px 0 0; }
        .rule { margin: 6px 0 16px; }
        .title-main {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 16pt;
            margin: 18px 0 6px;
            text-align: center;
        }
        .code-line { text-align: center; margin: 0 0 16px; }
        .party-title {
            font-weight: bold;
            text-transform: uppercase;
            margin: 16px 0 6px;
        }
        .line { margin: 3px 0; }
        table.info {
            width: 100%;
            border-collapse: collapse;
            margin: 14px 0 18px;
        }
        table.info td {
            border: 1px solid #333;
            padding: 7px 10px;
            vertical-align: top;
        }
        table.info td.label {
            width: 34%;
            font-weight: bold;
            background: #f5f5f5;
        }
        .section-title {
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
            margin: 20px 0 10px;
        }
        .terms-body {
            white-space: pre-wrap;
            text-align: justify;
            margin: 0;
        }
        .signatures {
            width: 100%;
            margin-top: 36px;
            border-collapse: collapse;
        }
        .signatures td {
            width: 50%;
            vertical-align: top;
            text-align: center;
            padding: 8px;
        }
        .sign-space { height: 88px; }
        .esign {
            margin-top: 28px;
            padding-top: 14px;
            border-top: 1px dashed #999;
            font-size: 11pt;
        }
        .hash { font-family: Consolas, monospace; font-size: 10pt; word-break: break-all; }
        .note { font-size: 11pt; color: #555; margin-top: 16px; font-style: italic; text-align: center; }
        .draft-banner {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            color: #92400e;
            border: 1px solid #f59e0b;
            background: #fffbeb;
            padding: 6px 10px;
            margin-bottom: 16px;
        }
        @media print {
            .no-print { display: none; }
            .draft-banner { border-color: #999; color: #333; background: #eee; }
            body { margin: 0; max-width: none; padding: 0; }
        }
    </style>
</head>
@php
    $typeMap = [
        'internship' => 'Hợp đồng thực tập',
        'probation' => 'Hợp đồng thử việc',
        'fixed_term' => 'Hợp đồng lao động xác định thời hạn',
        'indefinite' => 'Hợp đồng lao động không xác định thời hạn',
        'official' => 'Hợp đồng lao động chính thức',
        'seasonal' => 'Hợp đồng thời vụ',
        'consultant' => 'Hợp đồng tư vấn',
    ];
    $type = $payload['contract_type'] ?? $contract->contract_type;
    $title = $contract->title
        ?: ($typeMap[$type] ?? 'Hợp đồng lao động');
    $start = $payload['start_date'] ?? optional($contract->start_date)?->toDateString();
    $end = $payload['end_date'] ?? optional($contract->end_date)?->toDateString();
    $fmt = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('d/m/Y') : '…/…/……';
    $base = (float) ($payload['base_salary'] ?? $contract->base_salary ?? $contract->salary ?? 0);
    $allowance = (float) ($payload['allowance'] ?? $contract->allowance ?? 0);
    $bonus = (float) ($contract->bonus ?? 0);
    $total = $base + $allowance + $bonus;
    $payment = match ($contract->payment_method) {
        'cash' => 'Tiền mặt',
        'bank_transfer' => 'Chuyển khoản',
        'cash_and_bank_transfer' => 'Tiền mặt và chuyển khoản',
        default => 'Tiền mặt và chuyển khoản',
    };
    $empName = $payload['employee_name'] ?? optional($contract->employee)->name ?? '…………………………';
    $empCode = $payload['employee_code'] ?? optional($contract->employee)->employee_code ?? '—';
    $dept = optional(optional($contract->employee)->department)->name ?? '—';
    $position = optional($contract->employee)->position ?? '—';
    $directorUser = $contract->directorSignature?->signer
        ?? \App\Models\User::query()->where('is_director', true)->with('employee')->orderBy('id')->first();
    $directorName = optional(optional($directorUser)->employee)->name
        ?? optional($directorUser)->name
        ?? 'Giám đốc';
    $workplace = $payload['workplace'] ?? $contract->workplace ?: 'Theo quy định công ty';
    $terms = $payload['terms'] ?? ($contract->contract_content ?: $contract->terms);
    $isDraftLike = in_array($contract->status, ['draft', 'rejected', 'pending'], true) && ! $contract->content_locked_at;
@endphp
<body>
    <p class="no-print">
        <button type="button" onclick="window.print()">In / Lưu PDF</button>
        @if(auth()->user()?->is_hr)
            <a href="{{ route('contracts.show', $contract) }}" style="margin-left:10px;">← Quay lại chi tiết (Gửi ký)</a>
        @endif
    </p>

    @if($isDraftLike)
        <div class="draft-banner">BẢN NHÁP / XEM TRƯỚC — CHƯA GỬI KÝ, CHƯA CÓ HIỆU LỰC PHÁP LÝ</div>
    @elseif($contract->isFullySigned())
        <div class="draft-banner" style="color:#166534;border-color:#86efac;background:#f0fdf4;">ĐÃ ĐỦ CHỮ KÝ HAI BÊN — {{ strtoupper($contract->statusLabel()) }}</div>
    @else
        <div class="draft-banner" style="color:#1d4ed8;border-color:#93c5fd;background:#eff6ff;">{{ strtoupper($contract->statusLabel()) }} — TÀI LIỆU ĐÃ KHÓA CHO LUỒNG KÝ SỐ</div>
    @endif

    <p class="center nation">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</p>
    <p class="center motto">Độc lập – Tự do – Hạnh phúc</p>
    <p class="center rule">——————</p>

    <p class="title-main">{{ $title }}</p>
    <p class="code-line">Số: <strong>{{ $payload['contract_code'] ?? $contract->contract_code }}</strong></p>

    <p>
        Hôm nay, ngày <strong>{{ now()->format('d/m/Y') }}</strong>, tại Công ty TNHH SmartHR, chúng tôi gồm:
    </p>

    <p class="party-title">Bên A — Người sử dụng lao động</p>
    <p class="line">Tên doanh nghiệp: <strong>Công ty TNHH SmartHR</strong></p>
    <p class="line">Đại diện: <strong>{{ $directorName }}</strong> &nbsp;&nbsp; Chức vụ: <strong>Giám đốc</strong></p>

    <p class="party-title">Bên B — Người lao động</p>
    <p class="line">Họ và tên: <strong>{{ $empName }}</strong></p>
    <p class="line">Mã nhân viên: <strong>{{ $empCode }}</strong></p>
    <p class="line">Email: <strong>{{ optional($contract->employee)->email ?: '—' }}</strong></p>
    <p class="line">Phòng ban: {{ $dept }} &nbsp;&nbsp; Chức vụ: <strong>{{ $position }}</strong></p>

    <p>Hai bên thỏa thuận ký kết hợp đồng lao động với các nội dung sau:</p>

    <table class="info">
        <tr>
            <td class="label">Loại hợp đồng</td>
            <td>{{ $typeMap[$type] ?? $type }}</td>
        </tr>
        <tr>
            <td class="label">Thời hạn</td>
            <td>Từ ngày <strong>{{ $fmt($start) }}</strong> đến ngày <strong>{{ $end ? $fmt($end) : 'Không xác định thời hạn' }}</strong></td>
        </tr>
        <tr>
            <td class="label">Nơi làm việc</td>
            <td>{{ $workplace }}</td>
        </tr>
        <tr>
            <td class="label">Lương cơ bản</td>
            <td><strong>{{ number_format($base, 0, ',', '.') }}</strong> VNĐ/tháng</td>
        </tr>
        <tr>
            <td class="label">Phụ cấp chức vụ</td>
            <td>{{ number_format($allowance, 0, ',', '.') }} VNĐ/tháng</td>
        </tr>
        <tr>
            <td class="label">Phụ cấp khác</td>
            <td>{{ number_format($bonus, 0, ',', '.') }} VNĐ/tháng</td>
        </tr>
        <tr>
            <td class="label">Tổng thu nhập</td>
            <td><strong>{{ number_format($total, 0, ',', '.') }}</strong> VNĐ/tháng</td>
        </tr>
        <tr>
            <td class="label">Hình thức thanh toán</td>
            <td>{{ $payment }}</td>
        </tr>
        @if($contract->benefits)
        <tr>
            <td class="label">Phúc lợi</td>
            <td>{{ $contract->benefits }}</td>
        </tr>
        @endif
    </table>

    @php
        $termsText = trim((string) $terms);
        $blocks = preg_split("/\n{2,}/", $termsText) ?: [];
    @endphp
    @foreach($blocks as $block)
        @php
            $lines = array_values(array_filter(array_map('trim', preg_split("/\n/", $block) ?: []), fn ($l) => $l !== ''));
            $head = $lines[0] ?? '';
            $isSection = (bool) preg_match('/^ĐIỀU KHOẢN/iu', $head);
        @endphp
        @if($isSection)
            <p class="section-title">{{ $head }}</p>
            @foreach(array_slice($lines, 1) as $line)
                <p class="line" style="text-align:justify;margin:4px 0;">{{ $line }}</p>
            @endforeach
        @else
            @foreach($lines as $line)
                <p class="line" style="text-align:justify;margin:4px 0;">{{ $line }}</p>
            @endforeach
        @endif
    @endforeach

    @if(!empty($payload['additional_terms']) || $contract->additional_terms)
        <p class="section-title">Điều khoản bổ sung</p>
        <pre class="terms-body">{{ $payload['additional_terms'] ?? $contract->additional_terms }}</pre>
    @endif

    <table class="signatures">
        <tr>
            <td>
                <p class="bold">ĐẠI DIỆN BÊN A</p>
                <p><em>(Ký, ghi rõ họ tên, đóng dấu)</em></p>
                <div class="sign-space">
                    @if($contract->director_signed_at)
                        <p class="bold" style="color:#166534;margin-top:24px;">✓ Đã ký số</p>
                        <p>{{ optional($contract->director_signed_at)->format('d/m/Y H:i') }}</p>
                    @endif
                </div>
                <p class="bold">{{ $directorName }}</p>
            </td>
            <td>
                <p class="bold">NGƯỜI LAO ĐỘNG — BÊN B</p>
                <p><em>(Ký, ghi rõ họ tên)</em></p>
                <div class="sign-space">
                    @if($contract->employee_signed_at)
                        <p class="bold" style="color:#166534;margin-top:24px;">✓ Đã ký số</p>
                        <p>{{ optional($contract->employee_signed_at)->format('d/m/Y H:i') }}</p>
                    @endif
                </div>
                <p class="bold">{{ $empName }}</p>
            </td>
        </tr>
    </table>

    <div class="esign no-print">
        <p><strong>Thông tin ký số (hệ thống):</strong> {{ $contract->statusLabel() }}</p>
        <p>Hash SHA-256: <span class="hash">{{ $contract->document_hash ?: 'Chưa khóa tài liệu' }}</span></p>
        <p>Xác thực hash: {{ ($hashValid ?? false) ? 'Hợp lệ' : ($contract->document_hash ? 'Chưa đủ / không khớp' : 'Chưa khóa') }}</p>
    </div>

    <p class="note">{{ $disclaimer ?? config('esign.disclaimer') }}</p>
</body>
</html>
