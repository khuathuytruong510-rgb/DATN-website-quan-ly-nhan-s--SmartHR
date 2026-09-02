<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>{{ $payload['contract_code'] ?? $contract->contract_code }} — Tài liệu hợp đồng</title>
    <style>
        body { font-family: "Times New Roman", serif; max-width: 800px; margin: 24px auto; color: #111; }
        h1 { text-align: center; font-size: 22px; }
        .meta { margin: 16px 0; }
        .meta div { margin: 4px 0; }
        .terms { white-space: pre-wrap; border: 1px solid #ccc; padding: 16px; min-height: 200px; }
        .hash { font-family: Consolas, monospace; font-size: 12px; word-break: break-all; }
        .note { font-size: 13px; color: #555; margin-top: 24px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <p class="no-print"><button type="button" onclick="window.print()">In / Lưu PDF</button></p>
    <h1>CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</h1>
    <p style="text-align:center;">Độc lập – Tự do – Hạnh phúc</p>
    <h1>HỢP ĐỒNG LAO ĐỘNG</h1>
    <p style="text-align:center;">Mã: <strong>{{ $payload['contract_code'] ?? $contract->contract_code }}</strong></p>

    <div class="meta">
        <div>Nhân viên: <strong>{{ $payload['employee_name'] ?? optional($contract->employee)->name }}</strong>
            ({{ $payload['employee_code'] ?? optional($contract->employee)->employee_code }})</div>
        <div>Loại hợp đồng: {{ $payload['contract_type'] ?? $contract->contract_type }}</div>
        <div>Thời hạn: {{ $payload['start_date'] ?? optional($contract->start_date)?->toDateString() }}
            — {{ $payload['end_date'] ?? optional($contract->end_date)?->toDateString() ?: 'Không xác định' }}</div>
        <div>Lương cơ bản: {{ number_format((float) ($payload['base_salary'] ?? $contract->base_salary ?? 0), 0, ',', '.') }} VNĐ</div>
        <div>Phụ cấp: {{ number_format((float) ($payload['allowance'] ?? $contract->allowance ?? 0), 0, ',', '.') }} VNĐ</div>
        <div>Nơi làm việc: {{ $payload['workplace'] ?? $contract->workplace ?: '—' }}</div>
    </div>

    <h3>Điều khoản</h3>
    <div class="terms">{{ $payload['terms'] ?? ($contract->contract_content ?: $contract->terms) }}</div>

    @if(!empty($payload['additional_terms']) || $contract->additional_terms)
        <h3>Điều khoản bổ sung</h3>
        <div class="terms">{{ $payload['additional_terms'] ?? $contract->additional_terms }}</div>
    @endif

    <h3>Chữ ký số (mô phỏng)</h3>
    <div class="meta">
        <div>Trạng thái: {{ $contract->statusLabel() }}</div>
        <div>Hash SHA-256: <span class="hash">{{ $contract->document_hash ?: 'Chưa khóa tài liệu' }}</span></div>
        @if($contract->director_signed_at)
            <div>Giám đốc ký (doanh nghiệp): {{ optional($contract->director_signed_at)->format('d/m/Y H:i') }}
                — {{ optional($contract->directorSignature?->signer)->name ?? 'Giám đốc' }}
                ({{ $contract->directorSignature?->provider_transaction_id }})</div>
        @endif
        @if($contract->employee_signed_at)
            <div>Nhân viên ký (người lao động): {{ optional($contract->employee_signed_at)->format('d/m/Y H:i') }}
                — {{ optional($contract->employeeSignature?->signer)->name ?? 'Nhân viên' }}
                ({{ $contract->employeeSignature?->provider_transaction_id }})</div>
        @endif
        <div>Xác thực hash: {{ ($hashValid ?? false) ? 'Hợp lệ' : ($contract->document_hash ? 'Chưa đủ/không khớp' : 'Chưa khóa') }}</div>
    </div>
    <p class="note">{{ $disclaimer ?? config('esign.disclaimer') }}</p>
</body>
</html>
