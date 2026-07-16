@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Chi tiết thanh toán - {{ $salaryPayment->code }}</h1>

    <div class="card">
        <div class="card-body">
            <p><strong>Nhân viên:</strong> {{ $salaryPayment->employee->name ?? '-' }}</p>
            <p><strong>Tháng:</strong> {{ $salaryPayment->month }}/{{ $salaryPayment->year }}</p>
            <p><strong>Thực lĩnh:</strong> {{ number_format($salaryPayment->net,2) }}</p>
            <p><strong>Hình thức:</strong> {{ $salaryPayment->payment_method }}</p>
            <p><strong>Ghi chú:</strong> {{ $salaryPayment->notes }}</p>

            @if($salaryPayment->status === 'pending' && auth()->user()->hasRole('accountant'))
                <form method="POST" action="{{ route('salary_payments.pay', $salaryPayment) }}">
                    @csrf
                    <input type="hidden" name="payment_method" value="cash">
                    <button class="btn btn-success">Thanh toán</button>
                </form>
            @endif
        </div>
    </div>

    <h3 class="mt-4">Lịch sử</h3>
    <ul>
        @foreach($salaryPayment->logs as $log)
            <li>{{ $log->created_at }} - {{ $log->action }} - {{ $log->user->name ?? 'System' }} - {{ $log->notes }}</li>
        @endforeach
    </ul>
</div>
@endsection
