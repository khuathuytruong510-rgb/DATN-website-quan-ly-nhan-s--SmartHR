@extends('layouts.app')

@section('title', 'Bổ nhiệm Giám đốc từ bên ngoài')

@section('content')
    <div class="page-head">
        <div>
            <p class="eyebrow">Quản trị chức vụ</p>
            <h1>Người mới chưa có trong SmartHR</h1>
            <p class="page-lead muted">Quyết định bổ nhiệm nằm ngoài hệ thống. Admin không tự lập hồ sơ nhân sự trên trang Người giữ chức — đúng phân quyền HR / Admin.</p>
        </div>
        <div class="page-actions">
            <a class="btn" href="{{ route('director_succession.index') }}">Quay lại người giữ chức</a>
        </div>
    </div>

    <div class="callout info">
        <p class="callout-title">Hai bước trước khi chọn trong danh sách</p>
        <ol>
            <li><strong>HR</strong> tạo hồ sơ nhân sự (chức vụ Giám đốc, Ban Giám đốc, đang làm việc). Chưa cấp quyền Director.</li>
            <li><strong>Admin</strong> tạo / kết nối tài khoản đăng nhập (role Nhân viên trước). Không đổi tên tài khoản Giám đốc cũ.</li>
            <li>Quay lại <a href="{{ route('director_succession.index') }}">Người giữ chức Giám đốc</a>, chọn người mới, kết thúc nhiệm kỳ người cũ.</li>
        </ol>
    </div>

    <div class="grid two-cols">
        <div class="card">
            <div class="card-head">
                <h2 class="card-title">Bước 1 — HR tạo hồ sơ</h2>
                <p class="card-lead">Gửi HR đường dẫn tạo hồ sơ. Form được gợi ý Ban Giám đốc / chức vụ Giám đốc. Hồ sơ tồn tại chưa đồng nghĩa đã có quyền Giám đốc.</p>
            </div>
            <p class="code-box"><code>{{ $hrCreateUrl }}</code></p>
            <p class="muted">Admin mở link này sẽ bị từ chối — chỉ HR được tạo hồ sơ nhân sự.</p>
        </div>
        <div class="card">
            <div class="card-head">
                <h2 class="card-title">Bước 2 — Admin tạo tài khoản</h2>
                <p class="card-lead">Sau khi HR đã lưu hồ sơ, trang Người giữ chức sẽ liệt kê hồ sơ chưa có tài khoản. Tạo tài khoản để kết nối, rồi mới cấp role Director khi cập nhật người giữ chức.</p>
            </div>
            <div class="actions">
                <a class="btn primary" href="{{ route('accounts.create') }}">Tạo tài khoản</a>
                <a class="btn" href="{{ route('director_succession.index') }}">Về danh sách người giữ chức</a>
            </div>
        </div>
    </div>
@endsection
