@extends('layouts.app')

@section('content')
@include('components.module_header', [
    'title' => 'Chấm công',
    'subtitle' => auth()->user()?->canManageHr()
        ? 'Quản lý và nhập liệu chấm công nhân viên.'
        : 'Xem dữ liệu chấm công phục vụ tính lương. Không chỉnh sửa.',
    'buttonText' => auth()->user()?->canManageHr() ? 'Thêm chấm công' : null,
    'buttonRoute' => auth()->user()?->canManageHr() ? route('attendance.create') : null,
])

@if(!empty($adjustmentRequests) && $adjustmentRequests->isNotEmpty() && (auth()->user()?->is_hr || auth()->user()?->is_admin))
<div class="card" style="margin-bottom:16px;">
    <h2>Yêu cầu điều chỉnh chấm công</h2>
    <table>
        <thead>
            <tr>
                <th>Nhân viên</th>
                <th>Ngày</th>
                <th>Giờ hiện tại</th>
                <th>Giờ đề nghị</th>
                <th>Lý do</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($adjustmentRequests as $req)
                <tr>
                    <td>{{ optional($req->employee)->name }}</td>
                    <td>{{ optional($req->work_date)->format('d/m/Y') }}</td>
                    <td>{{ $req->current_check_in ?: '—' }} / {{ $req->current_check_out ?: '—' }}</td>
                    <td>{{ $req->requested_check_in ?: '—' }} / {{ $req->requested_check_out ?: '—' }}</td>
                    <td>{{ $req->reason }}</td>
                    <td>
                        <form method="POST" action="{{ route('attendance.adjustments.approve', $req) }}" style="display:inline">
                            @csrf
                            <input type="hidden" name="review_note" value="HR duyệt yêu cầu điều chỉnh">
                            <button class="btn" type="submit">Duyệt</button>
                        </form>
                        <form method="POST" action="{{ route('attendance.adjustments.reject', $req) }}" style="display:inline">
                            @csrf
                            <input type="text" name="review_note" placeholder="Lý do từ chối" style="max-width:140px;">
                            <button class="btn" type="submit">Từ chối</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="card">
    @if($attendances->count())
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Nhân viên</th>
                        <th>Ngày</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Trạng thái</th>
                        <th>Ghi chú</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($attendances as $attendance)
                        <tr>
                            <td>{{ optional($attendance->employee)->name }}</td>
                            <td>{{ \Carbon\Carbon::parse($attendance->date)->format('d/m/Y') }}</td>
                            <td>{{ $attendance->check_in?->format('Y-m-d H:i:s') ?? '-' }}</td>
                            <td>{{ $attendance->check_out?->format('Y-m-d H:i:s') ?? '-' }}</td>
                            <td>
                                @php
                                    $status = $attendance->status;
                                    $map = [
                                        'present' => 'success',
                                        'late' => 'warning',
                                        'leave_early' => 'info',
                                        'leave' => 'primary',
                                        'absent' => 'danger',
                                        'overtime' => 'secondary',
                                    ];
                                    $badge = $map[$status] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $badge }}">{{ $attendance->status_label }}</span>
                            </td>
                            <td>{{ $attendance->notes ?: '-' }}</td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a class="text-primary" href="{{ route('attendances.show', $attendance) }}">Xem</a>
                                    @if(auth()->user()?->canManageHr())
                                    <a class="text-primary" href="{{ route('attendance.edit', $attendance) }}">Sửa</a>
                                    <form action="{{ route('attendance.destroy', $attendance) }}" method="POST" style="display:inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger" type="submit" onclick="return confirm('Xóa bản ghi chấm công?')">Xóa</button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pagination">{{ $attendances->links() }}</div>
    @else
        <div class="empty">Không có bản ghi chấm công.</div>
    @endif
</div>
@endsection
