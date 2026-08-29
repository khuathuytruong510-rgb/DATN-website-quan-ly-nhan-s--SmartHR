@extends('layouts.app')

@section('title', 'Tính lương')

@section('content')
@section('breadcrumb')
<li><a href="{{ route('accountant.dashboard') }}">Kế toán</a></li>
<li>Tính lương</li>
@endsection

<div class="page-head">
    <div>
        <h1>Tính lương</h1>
        <p class="muted">Kỳ đang xử lý: HR đã chốt → Kế toán tính (hoặc tính lại phiếu nháp / đã tính / sự cố). Không tính lại phiếu HR đã kiểm tra, Giám đốc đã duyệt, NV đã xác nhận hoặc đã thanh toán.</p>
    </div>
    <div class="actions">
        <a class="btn" href="{{ route('accountant.payroll.index') }}">Sang bảng lương</a>
    </div>
</div>

@if(session('success'))
    <div class="alert">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert" style="background:#fee2e2;color:#dc2626;">{{ session('error') }}</div>
@endif

<div class="card">
    <h3 style="margin-top:0;">Kỳ đang xử lý</h3>
    <table>
        <thead>
            <tr>
                <th>Kỳ</th>
                <th>HR chốt</th>
                <th>Phiếu</th>
                <th>Đã tính / sự cố</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($periods as $period)
                <tr>
                    <td><strong>Tháng {{ $period['label'] }}</strong></td>
                    <td>
                        @if($period['locked'])
                            <span class="badge">Đã chốt</span>
                        @else
                            <span class="muted">Chưa chốt</span>
                        @endif
                    </td>
                    <td>{{ $period['total'] }}</td>
                    <td>{{ $period['calculated'] }} / {{ $period['issue'] }}</td>
                    <td style="text-align:right;">
                        @if($period['locked'])
                            <form method="POST" action="{{ route('accountant.payroll.generate_post') }}" style="display:inline;">
                                @csrf
                                <input type="hidden" name="month" value="{{ $period['value'] }}">
                                <button class="btn primary" type="submit">Tính lương</button>
                            </form>
                        @else
                            <span class="muted">Chờ HR chốt kỳ</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="card" style="margin-top:16px;">
    <h3 style="margin-top:0;">Kỳ khác</h3>
    <form method="POST" action="{{ route('accountant.payroll.generate_post') }}">
        @csrf
        <div class="field">
            <label for="month">Chọn tháng</label>
            <input id="month" name="month" type="month" value="{{ now()->format('Y-m') }}" required>
        </div>
        <button class="btn primary" type="submit">Tính lương</button>
        <p class="muted" style="margin-top:10px;font-size:13px;">Hệ thống tự tính số liệu và gán trạng thái Đã tính. Client không gửi được status / total_salary.</p>
    </form>
</div>

@endsection
