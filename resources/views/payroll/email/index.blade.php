@extends('layouts.app')

@section('title', 'Gửi phiếu lương')

@section('content')
    @include('components.module_header', [
        'title' => 'Gửi phiếu lương',
        'subtitle' => 'Quản lý gửi email phiếu lương cho nhân viên.',
        'buttonText' => 'Gửi tất cả',
        'buttonRoute' => '#',
    ])

    @php
        $total = $payrolls->count();
        $sentCount = $payrolls->where('email_status', 'sent')->count();
        $failedCount = $payrolls->where('email_status', 'failed')->count();
        $pendingCount = $total - $sentCount - $failedCount;
    @endphp

    <div class="grid stats" style="margin-bottom:20px;">
        <div class="card">
            <div class="muted">Tổng phiếu lương</div>
            <div class="stat-value" style="font-size:32px;">{{ $total }}</div>
        </div>
        <div class="card">
            <div class="muted">Đã gửi</div>
            <div class="stat-value" style="font-size:32px; color:#16a34a;">{{ $sentCount }}</div>
        </div>
        <div class="card">
            <div class="muted">Chưa gửi / Lỗi</div>
            <div class="stat-value" style="font-size:32px; color:#ea580c;">{{ $pendingCount + $failedCount }}</div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert" style="background:#dcfce7; color:#166534; border-left:4px solid #16a34a;">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert" style="background:#fee2e2; color:#991b1b; border-left:4px solid #dc2626;">
            {{ session('error') }}
        </div>
    @endif

    @if(session('send_errors'))
        <div class="alert" style="background:#fef3c7; color:#92400e; border-left:4px solid #f59e0b;">
            <strong>Một số email không gửi được:</strong>
            <ul style="margin:8px 0 0 0; padding-left:18px;">
                @foreach(session('send_errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card" style="padding:0; overflow:hidden;">
        <table>
            <thead>
                <tr>
                    <th>Nhân viên</th>
                    <th>Tháng</th>
                    <th>Email</th>
                    <th style="text-align:right;">Lương cơ bản</th>
                    <th style="text-align:right;">Thực nhận</th>
                    <th>Trạng thái</th>
                    <th style="text-align:right;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payrolls as $payroll)
                    <tr>
                        <td style="font-weight:600;">{{ $payroll->employee->name ?? 'N/A' }}</td>
                        <td>{{ $payroll->display_month }}</td>
                        <td>{{ $payroll->employee->email ?? 'N/A' }}</td>
                        <td style="text-align:right;">{{ number_format($payroll->base_salary, 0, '.', ',') }} VNĐ</td>
                        <td style="text-align:right; font-weight:600;">{{ number_format($payroll->total_salary, 0, '.', ',') }} VNĐ</td>
                        <td>
                            @php $status = $payroll->email_status ?? 'pending'; @endphp
                            @if($status === 'sent')
                                <span class="badge" style="background:#dcfce7; color:#166534;">Đã gửi</span>
                            @elseif($status === 'failed')
                                <span class="badge" style="background:#fee2e2; color:#991b1b;">Lỗi</span>
                            @else
                                <span class="badge pending">Chưa gửi</span>
                            @endif
                        </td>
                        <td style="text-align:right;">
                            <div class="actions" style="justify-content:flex-end;">
                                <form action="{{ route('payroll.email.send', $payroll) }}" method="POST" style="margin:0;">
                                    @csrf
                                    <button class="btn primary" type="submit" style="padding:7px 14px; font-size:13px;">
                                        {{ $status === 'sent' ? 'Gửi lại' : 'Gửi' }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty">Chưa có phiếu lương để gửi.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <script>
        document.querySelector('.module-primary-btn').addEventListener('click', function(e) {
            e.preventDefault();
            var sendAll = function () {
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("payroll.email.send_all") }}';

                var csrf = document.createElement('input');
                csrf.type = 'hidden';
                csrf.name = '_token';
                csrf.value = '{{ csrf_token() }}';
                form.appendChild(csrf);

                document.body.appendChild(form);
                form.submit();
            };
            if (typeof window.SmartHrConfirm === 'function') {
                SmartHrConfirm('Bạn có chắc muốn gửi tất cả phiếu lương qua email?', sendAll);
            } else {
                sendAll();
            }
        });
    </script>
@endsection
