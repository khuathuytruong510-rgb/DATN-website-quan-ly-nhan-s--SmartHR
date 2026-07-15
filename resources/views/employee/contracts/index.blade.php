<h1>Hợp đồng của tôi</h1>

@section('breadcrumb')
<li><a href="{{ route('me.dashboard') }}">Dashboard</a></li>
<li>Hợp đồng</li>
@endsection

@if($contracts->isEmpty())
    <p>Không có hợp đồng.</p>
@else
    <ul>
    @foreach($contracts as $c)
        <li>{{ $c->title ?? 'Hợp đồng' }} — {{ $c->start_date ?? '-' }} → {{ $c->end_date ?? '-' }}</li>
    @endforeach
    </ul>
@endif
