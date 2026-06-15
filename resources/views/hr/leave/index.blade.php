@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Đơn nghỉ phép</h1>

    @if($leaveRequests->count())
        <table class="table">
            <thead>
                <tr>
                    <th>Nhân viên</th>
                    <th>Từ ngày</th>
                    <th>Đến ngày</th>
                    <th>Số ngày</th>
                    <th>Loại</th>
                    <th>Lý do</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                @foreach($leaveRequests as $l)
                    <tr>
                        <td>{{ optional($l->employee)->name }}</td>
                        <td>{{ $l->start_date }}</td>
                        <td>{{ $l->end_date }}</td>
                        <td>{{ $l->days }}</td>
                        <td>{{ $l->type }}</td>
                        <td>{{ $l->reason }}</td>
                        <td>{{ $l->status }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $leaveRequests->links() }}
    @else
        <p>Không có đơn nghỉ phép.</p>
    @endif
</div>
@endsection
