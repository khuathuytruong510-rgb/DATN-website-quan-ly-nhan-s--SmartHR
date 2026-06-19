<h1>Đơn nghỉ phép của tôi</h1>

@if($leaves->isEmpty())
    <p>Chưa có đơn nghỉ phép.</p>
@else
    <ul>
    @foreach($leaves as $leave)
        <li>{{ $leave->start_date->toDateString() }} → {{ $leave->end_date->toDateString() }} — {{ $leave->type }} — {{ $leave->status }}</li>
    @endforeach
    </ul>
@endif

<p><a href="{{ route('me.leave_requests.create') }}">Tạo đơn mới</a></p>
