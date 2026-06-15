@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Hợp đồng</h1>
    <a class="btn" href="{{ route('contracts.create') }}">Tạo hợp đồng</a>

    @if($contracts->count())
        <table class="table">
            <thead>
                <tr>
                    <th>Nhân viên</th>
                    <th>Tiêu đề</th>
                    <th>Lương</th>
                    <th>Thời hạn</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                @foreach($contracts as $contract)
                    <tr>
                        <td>{{ optional($contract->employee)->name }}</td>
                        <td>{{ $contract->title }}</td>
                        <td>{{ $contract->salary }}</td>
                        <td>{{ $contract->start_date }} - {{ $contract->end_date }}</td>
                        <td>{{ $contract->status }}</td>
                        <td>
                            <a href="{{ route('contracts.edit', $contract) }}">Sửa</a>
                            <form action="{{ route('contracts.destroy', $contract) }}" method="POST" style="display:inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('Xóa hợp đồng?')">Xóa</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $contracts->links() }}
    @else
        <p>Chưa có hợp đồng.</p>
    @endif
</div>
@endsection
