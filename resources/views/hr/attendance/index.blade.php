@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <h1>Chấm công</h1>
        <p class="muted">Quản lý và nhập liệu chấm công nhân viên.</p>
    </div>
    <a class="btn primary" href="{{ route('attendance.create') }}">Thêm chấm công</a>
</div>

<div class="card">
    @if($attendances->count())
        <table>
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
                        <td>{{ $attendance->check_in ?: '-' }}</td>
                        <td>{{ $attendance->check_out ?: '-' }}</td>
                        <td>
                            <span class="badge {{ $attendance->status === 'absent' ? 'inactive' : ($attendance->status === 'late' ? 'pending' : '') }}">
                                {{ ucfirst($attendance->status) }}
                            </span>
                        </td>
                        <td>{{ $attendance->notes ?: '-' }}</td>
                        <td>
                            <div class="actions">
                                <a class="btn link" href="{{ route('attendance.edit', $attendance) }}">Sửa</a>
                                <form action="{{ route('attendance.destroy', $attendance) }}" method="POST" style="display:inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn danger" type="submit" onclick="return confirm('Xóa bản ghi chấm công?')">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="pagination">{{ $attendances->links() }}</div>
    @else
        <div class="empty">Không có bản ghi chấm công.</div>
    @endif
</div>
@endsection
