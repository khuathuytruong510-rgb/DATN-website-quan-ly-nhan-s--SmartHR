@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Mẫu hợp đồng</h2>
            <p class="text-muted mb-0">Quản lý điều khoản mặc định cho hợp đồng.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('contract-templates.create') }}">Tạo mẫu</a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Tiêu đề</th>
                        <th>Loại hợp đồng</th>
                        <th>Trạng thái</th>
                        <th>Mặc định</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($templates as $template)
                        <tr>
                            <td>{{ $template->title }}</td>
                            <td>{{ match($template->contract_type) { 'probation' => 'Thử việc', 'fixed_term' => 'Xác định thời hạn', 'indefinite' => 'Không xác định thời hạn', 'internship' => 'Thực tập', 'official' => 'Lao động chính thức', 'seasonal' => 'Thời vụ', 'consultant' => 'Cộng tác viên', default => '—' } }}</td>
                            <td>{{ $template->status === 'active' ? 'Hoạt động' : 'Không hoạt động' }}</td>
                            <td>{{ $template->is_default ? 'Có' : 'Không' }}</td>
                            <td>{{ $template->created_at->format('d/m/Y') }}</td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('contract-templates.edit', $template) }}">Sửa</a>
                                    <form action="{{ route('contract-templates.destroy', $template) }}" method="POST" data-confirm="Xóa mẫu này?">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
