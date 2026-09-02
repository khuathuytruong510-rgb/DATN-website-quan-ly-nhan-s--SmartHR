@extends('layouts.app')

@section('title', 'Chi tiết yêu cầu xóa')
@php
    $user = auth()->user();
    $isManager = $user && ($user->is_hr || $user->is_admin);
    $isDirector = $user && $user->is_director;
    $badge = match ($request->status) {
        'pending' => 'background:#fef3c7;color:#92400e;',
        'approved' => 'background:#dbeafe;color:#1e40af;',
        'applied' => 'background:#dcfce7;color:#166534;',
        'rejected' => 'background:#fee2e2;color:#991b1b;',
        default => 'background:#e2e8f0;color:#475569;',
    };
    $targetLink = null;
    if (! $request->isApplied() && $request->requestable) {
        $targetLink = $request->kind === 'employee'
            ? route('employees.show', $request->requestable)
            : route('departments.show', $request->requestable);
    }
    $snapshot = (array) ($request->payload ?? []);
@endphp
@section('content')
<div class="content" style="max-width:960px;">
    <div class="page-head">
        <div>
            <h1>Chi tiết yêu cầu xóa</h1>
            <p class="muted">{{ $request->code }} · {{ $request->kindLabel() }} · {{ $request->name }}</p>
        </div>
        <div class="actions">
            <a class="btn link" href="{{ route('deletion_requests.index') }}">← Danh sách</a>
            @if($targetLink)
                <a class="btn" href="{{ $targetLink }}">Xem hồ sơ</a>
            @endif
        </div>
    </div>

    <div class="card" style="padding:0;overflow:hidden;margin-bottom:16px;">
        <table>
            <tbody>
                <tr>
                    <th style="width:220px;background:#f8fafc;">Mã yêu cầu</th>
                    <td><code>{{ $request->code }}</code></td>
                    <th style="width:200px;background:#f8fafc;">Trạng thái</th>
                    <td><span class="badge" style="{{ $badge }}">{{ $request->statusLabel() }}</span></td>
                </tr>
                <tr>
                    <th style="background:#f8fafc;">Đối tượng</th>
                    <td>{{ $request->kindLabel() }}</td>
                    <th style="background:#f8fafc;">Tên</th>
                    <td><strong>{{ $request->name }}</strong></td>
                </tr>
                <tr>
                    <th style="background:#f8fafc;">Người tạo</th>
                    <td>{{ optional($request->submittedBy)->name ?? '—' }} <span class="muted">({{ optional($request->created_at)->format('d/m/Y H:i') }})</span></td>
                    <th style="background:#f8fafc;">Thời gian tạo</th>
                    <td>{{ optional($request->created_at)->format('d/m/Y H:i') ?? '—' }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="card" style="margin-bottom:16px;">
        <h3 style="margin-top:0;">Lý do yêu cầu xóa</h3>
        <p style="white-space:pre-wrap;margin:0;">{{ $request->reason ?: '—' }}</p>
    </div>

    @if($snapshot)
        <div class="card" style="margin-bottom:16px;">
            <h3 style="margin-top:0;">Thông tin tại thời điểm tạo yêu cầu</h3>
            <div class="grid two-cols">
                @foreach($snapshot as $key => $value)
                    @if($key === 'password' || $key === 'avatar')
                        @continue
                    @endif
                    <div class="field">
                        <label>{{ ucfirst(str_replace('_', ' ', $key)) }}</label>
                        <div>
                            @if(is_bool($value))
                                {{ $value ? 'Có' : 'Không' }}
                            @elseif($key === 'birthday' || $key === 'start_date' || $key === 'end_date')
                                {{ $value ? date('d/m/Y', strtotime($value)) : '—' }}
                            @elseif($value === null || $value === '')
                                —
                            @else
                                {{ is_scalar($value) ? $value : json_encode($value) }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($request->isApproved() || $request->isRejected() || $request->isCancelled())
        <div class="card" style="margin-bottom:16px;">
            <h3 style="margin-top:0;">Phê duyệt</h3>
            <div class="field">
                <label>Người duyệt</label>
                <div>{{ optional($request->reviewedBy)->name ?? '—' }} <span class="muted">({{ optional($request->reviewed_at)->format('d/m/Y H:i') }})</span></div>
            </div>
            <div class="field">
                <label>Nhận xét</label>
                <div style="white-space:pre-wrap;">{{ $request->review_note ?: '—' }}</div>
            </div>
            @if($request->isCancelled())
                <div class="field">
                    <label>Lý do hủy</label>
                    <div style="white-space:pre-wrap;">{{ $request->cancellation_note ?: '—' }}</div>
                </div>
            @endif
        </div>
    @endif

    @if($request->isApplied())
        <div class="card" style="margin-bottom:16px;">
            <h3 style="margin-top:0;">Thực hiện xóa</h3>
            <div class="field">
                <label>Người thực hiện</label>
                <div>{{ optional($request->appliedBy)->name ?? '—' }} <span class="muted">({{ optional($request->applied_at)->format('d/m/Y H:i') }})</span></div>
            </div>
        </div>
    @endif

    <div class="card" style="display:flex;gap:12px;flex-wrap:wrap;">
        @if($request->isPending() && $isDirector)
            <form method="POST" action="{{ route('deletion_requests.approve', $request) }}">
                @csrf
                <div class="mb-2">
                    <label class="form-label" for="review_note">Ghi chú duyệt</label>
                    <input type="text" name="review_note" id="review_note" class="form-control" maxlength="500" value="{{ old('review_note') }}">
                </div>
                <button class="btn primary" type="submit" data-confirm="Duyệt yêu cầu xóa này? HR sẽ thực hiện xóa.">Duyệt yêu cầu xóa</button>
            </form>
            <form method="POST" action="{{ route('deletion_requests.reject', $request) }}">
                @csrf
                <div class="mb-2">
                    <label class="form-label" for="review_note_reject">Lý do từ chối <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="review_note" id="review_note_reject" class="form-control" maxlength="500" required>
                </div>
                <button class="btn danger" type="submit" data-confirm="Từ chối yêu cầu xóa này?">Từ chối</button>
            </form>
        @elseif($request->isApproved() && $isManager)
            <div style="background:#dbeafe;color:#1e40af;padding:10px 14px;border-radius:8px;width:100%;">
                Đã được Giám đốc duyệt.
                @if(! $request->requestable)
                    <strong>Đối tượng đã không còn tồn tại.</strong>
                @endif
            </div>
            @if($request->requestable)
                <form method="POST" action="{{ route('deletion_requests.execute', $request) }}">
                    @csrf
                    <button class="btn primary" type="submit" data-confirm="Thực hiện xóa? Hành động này không thể hoàn tác.">Thực hiện xóa ngay</button>
                </form>
            @endif
            <form method="POST" action="{{ route('deletion_requests.cancel', $request) }}">
                @csrf
                <input type="hidden" name="cancellation_note" value="Hủy yêu cầu xóa đã duyệt">
                <button class="btn" type="submit" data-confirm="Hủy yêu cầu xóa đã duyệt này?">Hủy yêu cầu</button>
            </form>
        @elseif($request->isPending() && $isManager)
            <form method="POST" action="{{ route('deletion_requests.cancel', $request) }}">
                @csrf
                <input type="hidden" name="cancellation_note" value="Hủy yêu cầu xóa đang chờ duyệt">
                <button class="btn" type="submit" data-confirm="Hủy yêu cầu xóa này?">Hủy yêu cầu</button>
            </form>
        @endif
    </div>
</div>
@endsection