@extends('layouts.app')

@section('title', 'Sự cố lương')

@section('content')
<div class="content" style="max-width:1100px;">
    <div class="page-head">
        <div>
            <h1>Sự cố lương từ nhân viên</h1>
            <p class="muted">Các phiếu lương nhân viên đã báo sai sót / sự cố</p>
        </div>
        <div class="actions">
            <a href="{{ route('payroll.index') }}" class="btn">← Bảng lương</a>
        </div>
    </div>

    <div class="card" style="padding:0;overflow:hidden;">
        <table>
            <thead>
                <tr>
                    <th>Nhân viên</th>
                    <th>Kỳ lương</th>
                    <th>Nội dung sự cố</th>
                    <th>Thời gian báo</th>
                    <th style="text-align:right;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($issues as $payroll)
                    <tr>
                        <td>
                            <strong>{{ optional($payroll->employee)->name }}</strong>
                            <div class="muted" style="font-size:13px;">{{ optional($payroll->employee)->email }}</div>
                        </td>
                        <td>
                            <div>{{ sprintf('%02d/%d', $payroll->month, $payroll->year) }}</div>
                            <div class="muted" style="font-size:13px;">#{{ $payroll->id }} · {{ $workflow->statusLabel($payroll->status) }}</div>
                        </td>
                        <td style="max-width:420px;white-space:pre-wrap;">{{ $payroll->issue_report }}</td>
                        <td>
                            {{ optional($payroll->issue_reported_at)?->format('d/m/Y H:i') ?? '—' }}
                        </td>
                        <td>
                            <div class="actions" style="justify-content:flex-end;">
                                <a href="{{ route('payroll.issues.fix_form', $payroll) }}" class="btn primary">Khắc phục</a>
                                <a href="{{ route('payroll.show', $payroll) }}" class="btn">Xem phiếu</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5"><div class="empty">Chưa có báo cáo sự cố nào.</div></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:16px;">{{ $issues->links() }}</div>
</div>
@endsection
