@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Bảng thanh toán lương</h1>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Mã</th>
                <th>Nhân viên</th>
                <th>Phòng ban</th>
                <th>Tháng</th>
                <th>Năm</th>
                <th>Tổng lương</th>
                <th>Khấu trừ</th>
                <th>Thực lĩnh</th>
                <th>Hình thức</th>
                <th>Trạng thái</th>
                <th>Người thanh toán</th>
                <th>Ngày thanh toán</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $p)
            <tr>
                <td>{{ $p->code }}</td>
                <td>{{ $p->employee->name ?? '-' }}</td>
                <td>{{ $p->employee->department->name ?? '-' }}</td>
                <td>{{ $p->month }}</td>
                <td>{{ $p->year }}</td>
                <td>{{ number_format($p->total,2) }}</td>
                <td>{{ number_format($p->deductions,2) }}</td>
                <td>{{ number_format($p->net,2) }}</td>
                <td>{{ $p->payment_method }}</td>
                <td>{{ $p->status }}</td>
                <td>{{ $p->paidBy->name ?? '-' }}</td>
                <td>{{ $p->paid_at }}</td>
                <td><a href="{{ route('salary_payments.show', $p) }}" class="btn btn-sm btn-primary">Xem</a></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{ $payments->links() }}
</div>
@endsection
