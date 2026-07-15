@extends('layouts.app')

@section('title', 'Gửi phiếu lương')

@section('content')
<div class="container-fluid">
    <div class="card shadow-lg border-0 rounded-4">
        <div class="card-header bg-white border-0 pt-4 px-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="mb-1 fw-bold text-secondary">
                        ✉️ Gửi phiếu lương
                    </h3>
                    <small class="text-muted">Quản lý gửi email phiếu lương cho nhân viên.</small>
                </div>
                <form action="{{ route('payroll.email.send_all') }}" method="POST">
                    @csrf
                    <button class="btn btn-primary" type="submit">Gửi tất cả</button>
                </form>
            </div>
        </div>

        <div class="card-body px-4">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if(session('send_errors'))
                <div class="alert alert-warning">
                    <strong>Một số email không gửi được:</strong>
                    <ul class="mb-0">
                        @foreach(session('send_errors') as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="text-secondary text-uppercase fs-7 fw-bold">
                        <tr>
                            <th>Nhân viên</th>
                            <th>Tháng</th>
                            <th>Email</th>
                            <th class="text-end">Lương cơ bản</th>
                            <th class="text-end">Thực nhận</th>
                            <th>Trạng thái gửi</th>
                            <th class="text-end">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payrolls as $payroll)
                            <tr>
                                <td>{{ $payroll->employee->name ?? 'N/A' }}</td>
                                <td>{{ $payroll->display_month }}</td>
                                <td>{{ $payroll->employee->email ?? 'N/A' }}</td>
                                <td class="text-end">{{ number_format($payroll->base_salary, 0, '.', ',') }} VNĐ</td>
                                <td class="text-end">{{ number_format($payroll->total_salary, 0, '.', ',') }} VNĐ</td>
                                <td>
                                    @php
                                        $status = $payroll->email_status ?? 'pending';
                                    @endphp
                                    @if($status === 'sent')
                                        <span class="badge bg-success">Đã gửi</span>
                                    @elseif($status === 'failed')
                                        <span class="badge bg-danger">Lỗi</span>
                                    @else
                                        <span class="badge bg-secondary">Chưa gửi</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <form action="{{ route('payroll.email.send', $payroll) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button class="btn btn-outline-primary btn-sm" type="submit">
                                            {{ ($payroll->email_status ?? 'pending') === 'sent' ? 'Gửi lại' : 'Gửi' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Chưa có phiếu lương để gửi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
