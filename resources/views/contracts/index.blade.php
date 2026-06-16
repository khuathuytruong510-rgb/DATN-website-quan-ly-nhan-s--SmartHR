@extends('layouts.app')

@section('content')
<div class="page-head">
    <div>
        <h1>Hợp đồng</h1>
        <p class="muted">Danh sách hợp đồng lao động và chi tiết điều khoản.</p>
    </div>
    <a class="btn primary" href="{{ route('contracts.create') }}">Tạo hợp đồng</a>
</div>

<div class="card">
    @if($contracts->count())
        <table>
            <thead>
                <tr>
                    <th>Nhân viên</th>
                    <th>Tiêu đề</th>
                    <th>Lương</th>
                    <th>Thời hạn</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @foreach($contracts as $contract)
                    <tr>
                        <td>{{ optional($contract->employee)->name }}</td>
                        <td>{{ $contract->title }}</td>
                        <td>{{ number_format($contract->salary, 0, ',', '.') }} VNĐ</td>
                        <td>{{ $contract->start_date }} - {{ $contract->end_date }}</td>
                        <td>
                            <span class="badge {{ $contract->status === 'expired' ? 'expired' : ($contract->status === 'pending' ? 'pending' : '') }}">
                                {{ ucfirst($contract->status) }}
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a class="btn link" href="{{ route('contracts.show', $contract) }}">Xem</a>
                                <a class="btn" href="{{ route('contracts.edit', $contract) }}">Sửa</a>
                                <form action="{{ route('contracts.destroy', $contract) }}" method="POST" style="display:inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn danger" type="submit" onclick="return confirm('Xóa hợp đồng?')">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="pagination">{{ $contracts->links() }}</div>
    @else
        <div class="empty">Chưa có hợp đồng.</div>
    @endif
</div>
@endsection
