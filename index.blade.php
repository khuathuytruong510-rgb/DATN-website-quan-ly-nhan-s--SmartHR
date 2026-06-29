@extends('layouts.app')

@section('content')

<div class="container">

    <h2>Bảng Lương</h2>

    <form action="{{ route('salary.generate') }}"
          method="POST">

        @csrf

        <button class="btn btn-primary">
            Tính lương tháng này
        </button>

    </form>

    <br>

    <table class="table table-bordered">

        <thead>
            <tr>
                <th>Nhân viên</th>
                <th>Chức vụ</th>
                <th>Ngày công</th>
                <th>Lương/ngày</th>
                <th>Phụ cấp</th>
                <th>Tổng lương</th>
            </tr>
        </thead>

        <tbody>

        @foreach($salaries as $salary)

            <tr>

                <td>
                    {{ $salary->employee->name }}
                </td>

                <td>
                    {{ $salary->employee->position }}
                </td>

                <td>
                    {{ $salary->working_days }}
                </td>

                <td>
                    {{ number_format($salary->daily_rate) }}
                </td>

                <td>
                    {{ number_format($salary->allowance) }}
                </td>

                <td>
                    <strong>
                        {{ number_format($salary->total_salary) }}
                    </strong>
                </td>

            </tr>

        @endforeach

        </tbody>

    </table>

</div>

@endsection