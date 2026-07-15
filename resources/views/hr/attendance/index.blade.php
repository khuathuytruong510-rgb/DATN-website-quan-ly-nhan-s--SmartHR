@extends('layouts.app')

@section('content')
@include('components.module_header', [
    'title' => 'Chấm công',
    'subtitle' => 'Quản lý và nhập liệu chấm công nhân viên.',
    'buttonText' => 'Thêm chấm công',
    'buttonRoute' => route('attendance.create'),
])

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
                                    <a class="text-primary" href="{{ route('attendance.edit', $attendance) }}">Sửa</a>
                                    <form action="{{ route('attendance.destroy', $attendance) }}" method="POST" style="display:inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger" type="submit" onclick="return confirm('Xóa bản ghi chấm công?')">Xóa</button>
                                    </form>
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
