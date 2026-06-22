<h1>Phòng ban của bạn</h1>

@if($department)
    <p><strong>Tên phòng ban:</strong> {{ $department->name ?? 'N/A' }}</p>
    <p><strong>Mô tả:</strong> {{ $department->description ?? 'Không có mô tả' }}</p>
@else
    <p>Bạn chưa được gán vào phòng ban nào.</p>
@endif

<p><a href="{{ route('me.dashboard') }}">Quay lại dashboard</a></p>
