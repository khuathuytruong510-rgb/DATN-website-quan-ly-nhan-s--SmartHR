<h1>Bảng lương của tôi</h1>

@if($payrolls->isEmpty())
    <p>Không có dữ liệu lương.</p>
@else
    <ul>
    @foreach($payrolls as $p)
        <li>{{ $p->month }} — {{ $p->gross_salary ?? '-' }} — {{ $p->net_salary ?? '-' }}</li>
    @endforeach
    </ul>
@endif
