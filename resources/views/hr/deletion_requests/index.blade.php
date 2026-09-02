@extends('layouts.app')

@section('title', 'Yêu cầu xóa')

@section('content')
@php
    $user = auth()->user();
    $isManager = $user && ($user->is_hr || $user->is_admin);
    $isDirector = $user && $user->is_director;
    $badgeMap = [
        'pending' => 'background:#fef3c7;color:#92400e;',
        'approved' => 'background:#dbeafe;color:#1e40af;',
        'applied' => 'background:#dcfce7;color:#166534;',
        'rejected' => 'background:#fee2e2;color:#991b1b;',
        'cancelled' => 'background:#e2e8f0;color:#475569;',
    ];
@endphp
<div class="content" style="max-width:1100px;">
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

    <div class="card" style="padding:0;overflow:hidden;">
        <table>
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
                        <td><code>{{ $req->code }}</code></td>
                        <td>
                            <span class="badge" style="background:#e0e7ff;color:#3730a3;">{{ $req->kindLabel() }}</span>
                            <div style="font-weight:600;margin-top:4px;">{{ $req->name }}</div>
                        </td>
                        <td style="max-width:280px;">
                            <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $req->reason }}">{{ $req->reason }}</div>
                        </td>
                        <td>{{ optional($req->submittedBy)->name ?? '—' }}</td>
                        <td>{{ optional($req->created_at)->format('d/m/Y H:i') }}</td>
                        <td>
                            <span class="badge" style="{{ $badgeMap[$req->status] ?? '' }}">{{ $req->statusLabel() }}</span>
                            @if ($req->reviewed_at)
                                <div class="muted" style="font-size:12px;margin-top:4px;">{{ optional($req->reviewed_at)->format('d/m/Y H:i') }}</div>
                            @endif
                        </td>
                        <td style="text-align:right;">
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