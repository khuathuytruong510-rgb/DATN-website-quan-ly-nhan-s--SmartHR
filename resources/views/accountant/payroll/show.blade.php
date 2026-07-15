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
        <form method="POST" action="{{ route('accountant.payroll.send_email', $payroll) }}" style="display:inline;">
            @csrf
            <button class="btn" type="submit">Gửi email</button>
        </form>
        <form method="POST" action="{{ route('accountant.payroll.recalculate', $payroll) }}" style="display:inline;">
            @csrf
            <button class="btn" type="submit">Tính lại</button>
        </form>
        @if(!$payroll->locked)
            <form method="POST" action="{{ route('accountant.payroll.lock', $payroll) }}" style="display:inline;">
                @csrf
                <button class="btn" type="submit">Khoá</button>
            </form>
        @else
            <form method="POST" action="{{ route('accountant.payroll.unlock', $payroll) }}" style="display:inline;">
                @csrf
                <button class="btn" type="submit">Mở khoá</button>
            </form>
        @endif
    </div>
</div>

<div class="card">
    <div style="margin-bottom:12px;">
        <strong>Tháng:</strong> {{ $payroll->month }}
    </div>
    <div style="margin-bottom:12px;">
        <strong>Nhân viên:</strong> {{ optional($payroll->employee)->name }} ({{ optional($payroll->employee)->email }})
    </div>
    <div style="margin-bottom:12px;">
        <strong>Tổng lương:</strong> {{ number_format($payroll->total_salary ?? 0,0,'.',',') }} VNĐ
    </div>
    <div>
        <strong>Trạng thái:</strong>
        @if($payroll->status === 'pending')<span class="badge pending">Chờ duyệt</span>
        @elseif($payroll->status === 'approved')<span class="badge">Đã duyệt</span>
        @elseif($payroll->status === 'paid')<span class="badge bg-success">Đã trả</span>
        @endif
    </div>
</div>

@endsection
