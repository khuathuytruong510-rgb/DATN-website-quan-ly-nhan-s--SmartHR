@extends('layouts.app')

@section('title', 'Chi tiết bảng lương')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('accountant.dashboard') }}">Kế toán</a></li>
<li><a href="{{ route('accountant.payroll.index') }}">Quản lý bảng lương</a></li>
<li>Chi tiết</li>
@endsection

<div class="page-head">
    <div>
        <h1>Chi tiết bảng lương</h1>
        <p class="muted">Bảng lương cho {{ optional($payroll->employee)->name }}</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('accountant.payroll.index') }}">Quay lại</a>
        @if($workflow->isDirectorApproved($payroll->status) || $workflow->canPay($payroll))
        <form method="POST" action="{{ route('accountant.payroll.send_email', $payroll) }}" style="display:inline;">
            @csrf
            <button class="btn" type="submit">Gửi email xác nhận</button>
        </form>
        @endif
        @if(in_array($payroll->status, \App\Services\PayrollPaymentWorkflowService::recalculableStatuses(), true))
        <form method="POST" action="{{ route('accountant.payroll.recalculate', $payroll) }}" style="display:inline;">
            @csrf
            <button class="btn" type="submit">Tính lại</button>
        </form>
        @endif
        @if($workflow->canPay($payroll))
        <a class="btn primary" href="{{ route('payroll.payment.show', $payroll) }}">Thanh toán</a>
        @endif
    </div>
</div>

<div class="card">
    <div style="margin-bottom:12px;">
        <strong>Tháng:</strong> {{ $payroll->display_month }}
    </div>
    <div style="margin-bottom:12px;">
        <strong>Nhân viên:</strong> {{ optional($payroll->employee)->name }} ({{ optional($payroll->employee)->email }})
    </div>
    <div style="margin-bottom:12px;">
        <strong>Tổng lương:</strong> {{ number_format($payroll->total_salary ?? 0,0,'.',',') }} VNĐ
    </div>
    <div>
        <strong>Trạng thái:</strong>
        @if($payroll->status === 'paid')<span class="badge bg-success">{{ $workflow->statusLabel($payroll->status) }}</span>
        @else<span class="badge">{{ $workflow->statusLabel($payroll->status) }}</span>
        @endif
    </div>
</div>

@endsection
