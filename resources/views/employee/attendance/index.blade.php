<h1>Chấm công của tôi</h1>

@if($attendances->isEmpty())
    <p>Chưa có bản ghi chấm công.</p>
@else
    <ul>
    @foreach($attendances as $a)
        <li>{{ $a->date }} — {{ $a->status }} — {{ $a->check_in ?? '-' }} / {{ $a->check_out ?? '-' }}</li>
    @endforeach
    </ul>
@endif

<p><a href="{{ route('me.attendance.create') }}">Thêm chấm công</a></p>
