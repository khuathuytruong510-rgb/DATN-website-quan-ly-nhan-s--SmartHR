@extends('layouts.app')

@section('title', 'Yêu cầu xóa')

@section('content')
@php
    $user = auth()->user();
    $isManager = $user && ($user->canManageHr() || $user->is_admin);
    $isDirector = $user && $user->canActAsDirector();
    $badgeMap = [
        'pending' => 'background:#fef3c7;color:#92400e;',
        'approved' => 'background:#dbeafe;color:#1e40af;',
        'applied' => 'background:#dcfce7;color:#166534;',
        'rejected' => 'background:#fee2e2;color:#991b1b;',
        'cancelled' => 'background:#e2e8f0;color:#475569;',
    ];
@endphp
<style>
    .deletion-requests-page { max-width: 100%; }
    .deletion-requests-table-wrap {
        flex: 0 0 auto;
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
    }
    .deletion-requests-table { width: 100%; min-width: 1200px; table-layout: fixed; }
    .deletion-requests-table th,
    .deletion-requests-table td { overflow-wrap: normal; }
    .deletion-requests-table code { display: inline-block; white-space: nowrap; }
    .deletion-requests-table .nowrap { white-space: nowrap; }
    .deletion-requests-table .request-subject-name {
        margin-top: 8px;
        font-weight: 700;
        line-height: 1.35;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
    }
    .deletion-requests-table .request-reason {
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
        line-height: 1.4;
    }
    .deletion-requests-table .actions { flex-wrap: wrap; gap: 6px; }
    .deletion-requests-table th,
    .deletion-requests-table td { padding: 12px 10px; }
    .deletion-requests-table col:nth-child(1) { width: 13% !important; }
    .deletion-requests-table col:nth-child(2) { width: 16% !important; }
    .deletion-requests-table col:nth-child(3) { width: 20% !important; }
    .deletion-requests-table col:nth-child(4) { width: 11% !important; }
    .deletion-requests-table col:nth-child(5) { width: 14% !important; }
    .deletion-requests-table col:nth-child(6) { width: 14% !important; }
    .deletion-requests-table col:nth-child(7) { width: 12% !important; }
    @media (max-width: 1100px) {
        .deletion-requests-table { min-width: 1200px; }
    }
    @media (max-width: 720px) {
        .deletion-requests-table { min-width: 1200px; }
        .deletion-requests-table th,
        .deletion-requests-table td { padding: 10px 8px; }
    }
</style>
<div class="content deletion-requests-page">
    <div class="page-head">
        <div>
            <h1>Yêu cầu xóa</h1>
            <p class="muted">Xóa nhân viên / phòng ban theo quy trình: HR tạo → Giám đốc duyệt → HR thực hiện.</p>
        </div>
    </div>

    <div class="card" style="padding:16px;margin-bottom:16px;">
        <form method="GET" action="{{ route('deletion_requests.index') }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
            <div>
                <label class="form-label" for="search">Tìm tên</label>
                <input type="text" id="search" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="Tên nhân viên / phòng ban">
            </div>
            <div>
                <label class="form-label" for="kind">Đối tượng</label>
                <select id="kind" name="kind" class="form-control">
                    <option value="">Tất cả</option>
                    <option value="employee" {{ ($filters['kind'] ?? '') === 'employee' ? 'selected' : '' }}>Nhân viên</option>
                    <option value="department" {{ ($filters['kind'] ?? '') === 'department' ? 'selected' : '' }}>Phòng ban</option>
                    <option value="transfer" {{ ($filters['kind'] ?? '') === 'transfer' ? 'selected' : '' }}>Điều chuyển nhân viên</option>
                </select>
            </div>
            <div>
                <label class="form-label" for="status">Trạng thái</label>
                <select id="status" name="status" class="form-control">
                    <option value="">Tất cả</option>
                    <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Chờ Giám đốc duyệt</option>
                    <option value="approved" {{ ($filters['status'] ?? '') === 'approved' ? 'selected' : '' }}>Đã duyệt — chờ xóa</option>
                    <option value="applied" {{ ($filters['status'] ?? '') === 'applied' ? 'selected' : '' }}>Đã xóa</option>
                    <option value="rejected" {{ ($filters['status'] ?? '') === 'rejected' ? 'selected' : '' }}>Từ chối</option>
                    <option value="cancelled" {{ ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                </select>
            </div>
            <button type="submit" class="btn">Lọc</button>
            <a class="btn link" href="{{ route('deletion_requests.index') }}">Bỏ lọc</a>
        </form>
    </div>

    <div class="card deletion-requests-table-wrap" style="padding:0;">
        <table class="deletion-requests-table">
            <colgroup>
                <col>
                <col>
                <col>
                <col>
                <col>
                <col>
                <col>
            </colgroup>
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Đối tượng</th>
                    <th>Lý do</th>
                    <th>Người tạo</th>
                    <th>Ngày tạo</th>
                    <th>Trạng thái</th>
                    <th style="text-align:right;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                    <tr>
                        <td class="nowrap"><code>{{ $req->code }}</code></td>
                        <td>
                            <span class="badge" style="background:#e0e7ff;color:#3730a3;">{{ $req->kindLabel() }}</span>
                            <div class="request-subject-name" title="{{ $req->name }}">{{ $req->name }}</div>
                        </td>
                        <td>
                            <div class="request-reason" title="{{ $req->reason }}">{{ $req->reason }}</div>
                        </td>
                        <td>{{ optional($req->submittedBy)->name ?? '—' }}</td>
                        <td class="nowrap">{{ optional($req->created_at)->format('d/m/Y H:i') }}</td>
                        <td class="nowrap">
                            <span class="badge" style="{{ $badgeMap[$req->status] ?? '' }}">{{ $req->statusLabel() }}</span>
                            @if ($req->reviewed_at)
                                <div class="muted" style="font-size:12px;margin-top:4px;">{{ optional($req->reviewed_at)->format('d/m/Y H:i') }}</div>
                            @endif
                        </td>
                        <td class="nowrap" style="text-align:right;">
                            <div class="actions" style="justify-content:flex-end;">
                                @if($req->isPending() && $isDirector)
                                    <form method="POST" action="{{ route('deletion_requests.approve', $req) }}">
                                        @csrf
                                        <button class="btn primary" type="submit" data-confirm="Duyệt yêu cầu xóa này?">Duyệt</button>
                                    </form>
                                    <form method="POST" action="{{ route('deletion_requests.reject', $req) }}">
                                        @csrf
                                        <input type="hidden" name="review_note" value="Không phù hợp tại thời điểm này">
                                        <button class="btn danger" type="submit" data-confirm="Từ chối yêu cầu xóa này?">Từ chối</button>
                                    </form>
                                @elseif($req->isApproved() && $isManager)
                                    <form method="POST" action="{{ route('deletion_requests.execute', $req) }}">
                                        @csrf
                                        <button class="btn primary" type="submit" data-confirm="Thực hiện xóa? Hành động này không thể hoàn tác.">Thực hiện xóa</button>
                                    </form>
                                    <form method="POST" action="{{ route('deletion_requests.cancel', $req) }}">
                                        @csrf
                                        <input type="hidden" name="cancellation_note" value="Hủy yêu cầu xóa">
                                        <button class="btn link" type="submit" data-confirm="Hủy yêu cầu xóa này?">Hủy</button>
                                    </form>
                                @elseif($req->isPending() && $isManager)
                                    <form method="POST" action="{{ route('deletion_requests.cancel', $req) }}">
                                        @csrf
                                        <input type="hidden" name="cancellation_note" value="Hủy yêu cầu xóa đang chờ duyệt">
                                        <button class="btn link" type="submit" data-confirm="Hủy yêu cầu xóa này?">Hủy</button>
                                    </form>
                                @endif
                                <a class="btn link" href="{{ route('deletion_requests.show', $req) }}">Xem</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7"><div class="empty">Chưa có yêu cầu xóa nào.</div></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:16px;">{{ $requests->links() }}</div>
</div>
@endsection
