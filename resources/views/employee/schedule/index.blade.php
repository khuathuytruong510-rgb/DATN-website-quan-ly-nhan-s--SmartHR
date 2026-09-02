@extends('layouts.app')

@section('title', 'Lịch làm việc')

@section('breadcrumb')
<li><a href="{{ route('me.dashboard') }}">Dashboard</a></li>
<li>Lịch làm việc</li>
@endsection

@section('content')
@php
    $contract = \App\Models\Contract::where('employee_id', $employee->id)
        ->whereIn('status', ['active', 'waiting_employee_signature', 'waiting_director_signature', 'waiting_employee', 'waiting_director'])
        ->latest('start_date')
        ->first()
        ?? \App\Models\Contract::where('employee_id', $employee->id)->latest()->first();
    $workplace = $contract->workplace ?? 'Trụ sở SmartHR, 12 Nguyễn Trãi, Thanh Xuân, Hà Nội';
    $shiftLabel = match ($contract->working_schedule ?? 'morning_evening') {
        'morning' => 'Ca sáng (08:00–12:00)',
        'evening' => 'Ca tối (13:00–17:00)',
        default => 'Hành chính (08:00–17:00, nghỉ trưa 12:00–13:00)',
    };
    $days = [
        ['Thứ Hai', '08:00', '17:00', 'Làm việc'],
        ['Thứ Ba', '08:00', '17:00', 'Làm việc'],
        ['Thứ Tư', '08:00', '17:00', 'Làm việc'],
        ['Thứ Năm', '08:00', '17:00', 'Làm việc'],
        ['Thứ Sáu', '08:00', '17:00', 'Làm việc'],
        ['Thứ Bảy', '08:00', '17:00', 'Làm việc'],
        ['Chủ nhật', '—', '—', 'Nghỉ'],
    ];
@endphp
    <div class="page-head">
        <div>
            <h1>Lịch làm việc</h1>
            <p class="muted">Lịch tuần theo hợp đồng đang áp dụng.</p>
        </div>
    </div>

    <div class="card" style="margin-bottom:16px;">
        <div class="grid two-cols">
            <div class="field"><label>Nhân viên</label><div>{{ $employee->name }} — {{ $employee->employee_code }}</div></div>
            <div class="field"><label>Phòng ban</label><div>{{ optional($employee->department)->name ?? '—' }}</div></div>
            <div class="field"><label>Nơi làm việc</label><div>{{ $workplace }}</div></div>
            <div class="field"><label>Ca làm việc</label><div>{{ $shiftLabel }}</div></div>
        </div>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Ngày</th>
                    <th>Giờ vào</th>
                    <th>Giờ ra</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                @foreach($days as [$day, $in, $out, $status])
                    <tr>
                        <td>{{ $day }}</td>
                        <td>{{ $in }}</td>
                        <td>{{ $out }}</td>
                        <td>{{ $status }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
