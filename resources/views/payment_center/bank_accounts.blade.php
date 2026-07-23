@extends('layouts.app')

@section('title', 'Thông tin ngân hàng nhân viên')

@section('content')

@section('breadcrumb')
<li><a href="{{ route('payment_center.dashboard') }}">Trung tâm thanh toán</a></li>
<li>Thông tin ngân hàng</li>
@endsection

<div class="page-head">
    <div>
        <h1>Thông tin ngân hàng nhân viên</h1>
        <p class="muted">Quản lý tài khoản ngân hàng用于 thanh toán lương</p>
    </div>
</div>

<div class="card" style="margin-bottom:20px;">
    <form method="GET" action="{{ route('payment_center.bank_accounts') }}" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
        <div class="field" style="margin-bottom:0; flex:1; min-width:200px;">
            <label>Tìm kiếm</label>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Tên, email, mã NV...">
        </div>
        <div class="field" style="margin-bottom:0; min-width:180px;">
            <label>Phòng ban</label>
            <select name="department_id">
                <option value="">Tất cả phòng ban</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                        {{ $dept->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <button class="btn primary" type="submit">Tìm kiếm</button>
        <a class="btn" href="{{ route('payment_center.bank_accounts') }}">Đặt lại</a>
    </form>
</div>

<div class="card">
    @if($employees && $employees->count())
        <table>
            <thead>
                <tr>
                    <th>Nhân viên</th>
                    <th>Email</th>
                    <th>Phòng ban</th>
                    <th>Ngân hàng</th>
                    <th>Số tài khoản</th>
                    <th>Chủ tài khoản</th>
                    <th>QR</th>
                    <th>Chỉnh sửa</th>
                </tr>
            </thead>
            <tbody>
                @foreach($employees as $emp)
                    <tr>
                        <td>
                            <strong>{{ $emp->name }}</strong><br>
                            <small class="muted">{{ $emp->employee_code ?? '' }}</small>
                        </td>
                        <td class="muted">{{ $emp->email }}</td>
                        <td>{{ $emp->department->name ?? '-' }}</td>
                        <td id="bank-name-{{ $emp->id }}">{{ $emp->bank_name ?? '-' }}</td>
                        <td id="account-number-{{ $emp->id }}">{{ $emp->account_number ?? '-' }}</td>
                        <td id="account-holder-{{ $emp->id }}">{{ $emp->account_holder ?? '-' }}</td>
                        <td>
                            @if($emp->bank_name && $emp->account_number)
                                <a class="btn link" href="{{ route('payment_center.qr_code', ['employee_id' => $emp->id]) }}">Xem QR</a>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td>
                            <button class="btn" type="button" onclick="toggleEdit({{ $emp->id }})">Sửa</button>
                        </td>
                    </tr>
                    <tr id="edit-row-{{ $emp->id }}" style="display:none;">
                        <td colspan="8" style="background:#f8fafc;">
                            <form method="POST" action="{{ route('payment_center.bank_accounts') }}" style="display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap; padding:12px 0;">
                                @csrf
                                <input type="hidden" name="employee_id" value="{{ $emp->id }}">
                                <div class="field" style="margin-bottom:0; min-width:160px;">
                                    <label>Ngân hàng</label>
                                    <input type="text" name="bank_name" value="{{ $emp->bank_name ?? '' }}" placeholder="Tên ngân hàng">
                                </div>
                                <div class="field" style="margin-bottom:0; min-width:160px;">
                                    <label>Số tài khoản</label>
                                    <input type="text" name="account_number" value="{{ $emp->account_number ?? '' }}" placeholder="Số tài khoản">
                                </div>
                                <div class="field" style="margin-bottom:0; min-width:160px;">
                                    <label>Chủ tài khoản</label>
                                    <input type="text" name="account_holder" value="{{ $emp->account_holder ?? '' }}" placeholder="Tên chủ tài khoản">
                                </div>
                                <button class="btn primary" type="submit">Lưu</button>
                                <button class="btn" type="button" onclick="toggleEdit({{ $emp->id }})">Hủy</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="pagination">{{ $employees->links() }}</div>
    @else
        <div class="empty">Không tìm thấy nhân viên nào.</div>
    @endif
</div>

@endsection

@push('scripts')
<script>
function toggleEdit(id) {
    var row = document.getElementById('edit-row-' + id);
    if (row.style.display === 'none') {
        row.style.display = '';
    } else {
        row.style.display = 'none';
    }
}
</script>
@endpush
