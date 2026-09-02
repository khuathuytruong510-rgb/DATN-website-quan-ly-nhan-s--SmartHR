@extends('layouts.app')

@section('content')
@php
    $typeMap = ['internship'=>'Thực tập','probation'=>'Thử việc','fixed_term'=>'Xác định thời hạn','indefinite'=>'Không xác định thời hạn','official'=>'Chính thức','seasonal'=>'Thời vụ'];
    $terms = $parent->contract_content ?: $parent->terms;
@endphp
<div class="contract-page">
    <div class="page-head">
        <div>
            <h1>Gia hạn hợp đồng</h1>
            <p class="muted">Chỉ kéo dài thời hạn. Toàn bộ nội dung hợp đồng {{ $parent->contract_code }} được giữ nguyên.</p>
        </div>
        <a class="btn link" href="{{ route('contracts.show', $parent) }}">Quay lại</a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div style="background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:10px;padding:14px 18px;margin-bottom:18px;">
        <div style="font-weight:700;color:#1d4ed8;margin-bottom:6px;">Gia hạn không đổi nội dung</div>
        <p class="muted" style="margin:0;">
            Hệ thống sao y loại hợp đồng, lương, phụ cấp và điều khoản từ hợp đồng cũ.
            Nếu cần đổi lương, chức vụ, loại hợp đồng hoặc điều khoản, hãy
            <a href="{{ route('contracts.create') }}">tạo hợp đồng mới</a>.
        </p>
    </div>

    <form method="POST" action="{{ route('contracts.storeRenewal', $parent) }}">
        @csrf

        <div class="card" style="margin-bottom:20px;">
            <div style="padding:6px 0 16px 0;border-bottom:1px solid var(--line);margin-bottom:16px;">
                <strong>Thời hạn mới</strong>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                <div class="field">
                    <label>Ngày bắt đầu <span class="text-danger">*</span></label>
                    <input type="date" name="start_date" value="{{ $newStart }}" required>
                    @error('start_date')<span class="error">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label>Ngày kết thúc {{ $parent->end_date ? '*' : '' }}</label>
                    <input type="date" name="end_date" value="{{ $newEnd }}" {{ $parent->end_date ? 'required' : '' }}>
                    @error('end_date')<span class="error">{{ $message }}</span>@enderror
                </div>
            </div>
            <p class="muted" style="margin:12px 0 0;font-size:13px;">
                Gợi ý: bắt đầu ngày liền sau ngày hết hạn hợp đồng cũ
                ({{ optional($parent->end_date)->format('d/m/Y') ?? '—' }}).
            </p>
        </div>

        <div class="card" style="margin-bottom:20px;">
            <div style="padding:6px 0 16px 0;border-bottom:1px solid var(--line);margin-bottom:16px;">
                <strong>Nội dung giữ nguyên từ hợp đồng cũ</strong>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px 18px;font-size:14px;">
                <div><span class="muted">Nhân viên</span><div style="font-weight:600;">{{ optional($parent->employee)->name ?? '—' }}</div></div>
                <div><span class="muted">Mã HĐ gốc</span><div style="font-weight:600;">{{ $parent->contract_code ?? '—' }}</div></div>
                <div><span class="muted">Loại hợp đồng</span><div style="font-weight:600;">{{ $typeMap[$parent->contract_type] ?? $parent->contract_type }}</div></div>
                <div><span class="muted">Thời hạn cũ</span><div style="font-weight:600;">{{ optional($parent->start_date)->format('d/m/Y') ?? '—' }} → {{ optional($parent->end_date)->format('d/m/Y') ?? 'Không XĐ' }}</div></div>
                <div><span class="muted">Lương cơ bản</span><div style="font-weight:600;">{{ number_format((float) ($parent->base_salary ?? $parent->salary ?? 0), 0, ',', '.') }}₫</div></div>
                <div><span class="muted">Phụ cấp</span><div style="font-weight:600;">{{ number_format((float) ($parent->allowance ?? 0), 0, ',', '.') }}₫</div></div>
            </div>
            <div class="field" style="margin-top:16px;">
                <label>Điều khoản hợp đồng</label>
                <pre style="white-space:pre-wrap;background:#f8fafc;border:1px solid var(--line);border-radius:8px;padding:14px 16px;max-height:320px;overflow:auto;font-family:inherit;line-height:1.7;margin:0;">{{ $terms !== '' && $terms !== null ? $terms : 'Hợp đồng cũ chưa có điều khoản.' }}</pre>
            </div>
        </div>

        <div class="actions">
            <button class="btn primary" type="submit">Tạo hợp đồng gia hạn</button>
            <a class="btn" href="{{ route('contracts.create') }}">Tạo hợp đồng mới (đổi nội dung)</a>
            <a class="btn" href="{{ route('contracts.show', $parent) }}">Hủy</a>
        </div>
        <p class="muted" style="margin:10px 0 0;font-size:13px;">Sau khi lưu, nhân viên ký rồi Giám đốc ký thì hợp đồng gia hạn mới có hiệu lực.</p>
    </form>
</div>
@endsection
