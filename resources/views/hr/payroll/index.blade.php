@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Danh sách Lương</h1>

    @if($payrolls->count())
        <table class="table">
            <thead>
                <tr>
                    <th>Nhân viên</th>
                    <th>Tháng</th>
                    <th>Lương cơ bản</th>
                    <th>Phụ cấp</th>
                    <th>Khấu trừ</th>
                    <th>Tổng</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payrolls as $p)
                    <tr>
                        <td>{{ optional($p->employee)->name }}</td>
                        <td>{{ $p->month }}</td>
                        <td>{{ $p->base_salary }}</td>
                        <td>{{ $p->allowance }}</td>
                        <td>{{ $p->deduction }}</td>
                        <td>{{ $p->total_salary }}</td>
                        <td>{{ $p->status }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $payrolls->links() }}
    @else
        <p>Không có bản ghi lương.</p>
    @endif
</div>
@endsection
