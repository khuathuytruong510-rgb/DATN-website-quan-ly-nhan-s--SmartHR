@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Chấm công</h1>

    @if($attendances->count())
        <table class="table">
            <thead>
                <tr>
                    <th>Nhân viên</th>
                    <th>Ngày</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Trạng thái</th>
                    <th>Ghi chú</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attendances as $a)
                    <tr>
                        <td>{{ optional($a->employee)->name }}</td>
                        <td>{{ $a->date }}</td>
                        <td>{{ $a->check_in }}</td>
                        <td>{{ $a->check_out }}</td>
                        <td>{{ $a->status }}</td>
                        <td>{{ $a->notes }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $attendances->links() }}
    @else
        <p>Không có bản ghi chấm công.</p>
    @endif
</div>
@endsection
