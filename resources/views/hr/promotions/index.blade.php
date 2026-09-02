@extends('layouts.app')

@section('title', 'Thăng chức / Tăng lương')

@section('content')
@php
    $user = auth()->user();
    $isManager = $user && ($user->is_hr || $user->is_admin);
    $isDirector = $user && $user->is_director;
@endphp
<div class="content" style="max-width:1100px;">
    <div class="page-head">
        <div>
            <h1>Thăng chức / Tăng lương</h1>
            <p class="muted">Đề xuất thay đổi chức vụ, mức lương theo quy trình: HR tạo → Giám đốc duyệt → HR áp dụng.</p>
        </div>
        <div class="actions">
            @if($isManager)
                <a class="btn" href="{{ route('promotion_requests.create') }}">+ Đề xuất mới</a>
            @endif
        </div>
    </div>

    <div class="card" style="padding:16px;margin-bottom:16px;">
        <form method="GET" action="{{ route('promotion_requests.index') }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
            <div>
                <label class="form-label" for="search">Tìm nhân viên</label>
                <input type="text" id="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Tên / mã nhân viên">
            </div>
            <div>
                <label class="form-label" for="status">Trạng thái</label>
                <select id="status" name="status" class="form-control">
                    <option value="">Tất cả</option>
                    <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Chờ Giám đốc duyệt</option>
                    <option value="approved" {{ ($filters['status'] ?? '') === 'approved' ? 'selected' : '' }}>Đã duyệt — chờ áp dụng</option>
                    <option value="applied" {{ ($filters['status'] ?? '') === 'applied' ? 'selected' : '' }}>Đã áp dụng</option>
                    <option value="rejected" {{ ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' }}>Từ chối</option>
                    <option value="cancelled" {{ ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                </select>
            </div>
            <button type="submit" class="btn">Lọc</button>
            <a class="btn link" href="{{ route('promotion_requests.index') }}">Bỏ lọc</a>
        </form>
    </div>

    <div class="card" style="padding:0;overflow:hidden;">
        <table>
            <thead>
                <tr>
                    <th>Nhân viên</th>
                    <th>Loại</th>
                    <th>Chức vụ</th>
                    <th>Lương CB (cũ → mới)</th>
                    <th>Hiệu lực</th>
                    <th>Trạng thái</th>
                    <th style="text-align:right;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $promo)
                    <tr>
                        <td>
                            <strong>{{ optional($promo->employee)->name ?? '—' }}</strong>
                            <div class="muted" style="font-size:13px;">{{ optional($promo->employee)->employee_code ?? '' }} · {{ optional(optional($promo->employee)->department)->name ?? '—' }}</div>
                        </td>
                        <td>
                            <span class="badge" style="background:#e0e7ff;color:#3730a3;">{{ $promo->changeTypeLabel() }}</span>
                            @if($promo->document_number)
                                <div class="muted" style="font-size:12px;margin-top:4px;">QĐ: {{ $promo->document_number }}</div>
                            @endif
                        </td>
                        <td>
                            @if($promo->hasPositionChange())
                                <div class="muted" style="font-size:12px;">{{ $promo->old_position ?: '—' }} →</div>
                                <div style="font-weight:600;">{{ $promo->new_position }}</div>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td>
                            <div class="muted" style="font-size:12px;">{{ number_format((float) $promo->old_base_salary, 0, ',', '.') }} ₫ →</div>
                            <div style="font-weight:600;color:var(--primary);">{{ number_format((float) $promo->new_base_salary, 0, ',', '.') }} ₫</div>
                        </td>
                        <td>{{ optional($promo->effective_date)->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            @php
                                $badge = match ($promo->status) {
                                    'pending' => 'background:#fef3c7;color:#92400e;',
                                    'approved' => 'background:#dbeafe;color:#1e40af;',
                                    'applied' => 'background:#dcfce7;color:#166534;',
                                    'rejected' => 'background:#fee2e2;color:#991b1b;',
                                    default => 'background:#e2e8f0;color:#475569;',
                                };
                            @endphp
                            <span class="badge" style="{{ $badge }}">{{ $promo->statusLabel() }}</span>
                        </td>
                        <td style="text-align:right;">
                            <div class="actions" style="justify-content:flex-end;">
                                @if($promo->isPending() && $isDirector)
                                    <form method="POST" action="{{ route('promotion_requests.approve', $promo) }}">
                                        @csrf
                                        <button class="btn primary" type="submit" data-confirm="Duyệt đề xuất này?">Duyệt</button>
                                    </form>
                                    <form method="POST" action="{{ route('promotion_requests.reject', $promo) }}">
                                        @csrf
                                        <input type="hidden" name="review_note" value="Không phù hợp tại thời điểm này">
                                        <button class="btn danger" type="submit" data-confirm="Từ chối đề xuất này?">Từ chối</button>
                                    </form>
                                @elseif($promo->isApproved() && $isManager)
                                    <form method="POST" action="{{ route('promotion_requests.apply', $promo) }}">
                                        @csrf
                                        <button class="btn primary" type="submit" data-confirm="Áp dụng đề xuất: cập nhật chức vụ, hợp đồng và lịch sử lương?">Áp dụng</button>
                                    </form>
                                    <form method="POST" action="{{ route('promotion_requests.cancel', $promo) }}">
                                        @csrf
                                        <input type="hidden" name="cancellation_note" value="Hủy đề xuất">
                                        <button class="btn link" type="submit" data-confirm="Hủy đề xuất này?">Hủy</button>
                                    </form>
                                @endif
                                <a class="btn link" href="{{ route('promotion_requests.show', $promo) }}">Xem</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7"><div class="empty">Chưa có đề xuất thăng chức / tăng lương nào.</div></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:16px;">{{ $requests->links() }}</div>
</div>
@endsection