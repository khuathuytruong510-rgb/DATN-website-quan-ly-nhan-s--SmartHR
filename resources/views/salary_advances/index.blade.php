@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Ứng lương</h1>

    <a href="{{ route('me.salary_advances.create') }}" class="btn btn-primary mb-3">Tạo yêu cầu</a>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Mã</th>
                <th>Nhân viên</th>
                <th>Tháng</th>
                <th>Số tiền</th>
                <th>Lý do</th>
                <th>Trạng thái</th>
                <th>Người duyệt</th>
                <th>Ngày duyệt</th>
            </tr>
        </thead>
        <tbody>
            @foreach($advances as $a)
            <tr>
                <td>{{ $a->code }}</td>
                <td>{{ $a->employee->name ?? '-' }}</td>
                <td>{{ $a->requested_at }}</td>
                <td>{{ number_format($a->amount,2) }}</td>
                <td>{{ \Illuminate\Support\Str::limit($a->reason, 50) }}</td>
                <td>{{ $a->status }}</td>
                <td>{{ $a->approver->name ?? '-' }}</td>
                <td>{{ $a->approved_at }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $advances->links() }}
</div>
@endsection
