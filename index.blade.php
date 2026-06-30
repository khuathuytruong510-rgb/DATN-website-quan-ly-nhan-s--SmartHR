@extends('layouts.app')

@section('content')

<div class="container">

    <div class="d-flex justify-content-between mb-3">

        <h3>Bảng lương nhân viên</h3>

        <form action="{{ route('payroll.generate') }}" method="POST">
            @csrf

            <button class="btn btn-primary">
                Tính lương tháng này
            </button>

        </form>

    </div>

    <table class="table table-bordered table-hover">

        <thead>

        <tr>

            <th>Nhân viên</th>

            <th>Tháng</th>

            <th>Lương cơ bản</th>

            <th>Ngày công</th>

            <th>Lương ngày công</th>

            <th>Tăng ca</th>

            <th>Phụ cấp</th>

            <th>Thưởng</th>

            <th>Bảo hiểm</th>

            <th>Thuế</th>

            <th>Thực nhận</th>

        </tr>

        </thead>

        <tbody>

        @foreach($payrolls as $payroll)

            <tr>

                <td>{{ $payroll->employee->name }}</td>

                <td>{{ $payroll->month }}/{{ $payroll->year }}</td>

                <td>{{ number_format($payroll->base_salary) }}</td>

                <td>
                    {{ $payroll->working_days }}
                    /
                    {{ $payroll->required_working_days }}
                </td>

                <td>{{ number_format($payroll->working_salary) }}</td>

                <td>{{ number_format($payroll->overtime_salary) }}</td>

                <td>{{ number_format($payroll->allowance) }}</td>

                <td>{{ number_format($payroll->bonus) }}</td>

                <td>{{ number_format($payroll->insurance) }}</td>

                <td>{{ number_format($payroll->tax) }}</td>

                <td>

                    <strong class="text-success">

                        {{ number_format($payroll->total_salary) }}

                    </strong>

                </td>

            </tr>

        @endforeach

        </tbody>

    </table>

</div>

@endsection