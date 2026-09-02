@extends('layouts.app')

@section('title', $subjectType === 'employee' ? 'Đề nghị xóa nhân viên' : 'Đề nghị xóa phòng ban')

@section('content')
@php
    $isEmployee = $subjectType === 'employee';
    $label = $isEmployee
        ? (($employee->employee_code ? $employee->employee_code.' — ' : '').$employee->name)
        : ('['.$department->code.'] '.$department->name);
    $staff = $employees ?? collect();
    $targets = $otherDepartments ?? collect();
    $pendingStaff = $pendingEmployeeDeletions ?? collect();
    $pendingTransfers = $pendingEmployeeTransfers ?? [];
    $hasStaff = ! $isEmployee && $staff->isNotEmpty();
@endphp
<div class="page-head">
    <div>
        <h1>{{ $isEmployee ? 'Đề nghị xóa nhân viên' : 'Đề nghị xóa phòng ban' }}</h1>
        <p class="muted">
            @if($isEmployee)
                Gửi Giám đốc duyệt. Chỉ xóa sau khi được phê duyệt. Cần lý do hoặc biên bản/tài liệu.
            @else
                Trước khi xóa phòng ban phải chuyển nhân viên sang phòng khác hoặc đề nghị xóa nhân viên. Chuyển phòng cũng gửi Giám đốc duyệt; nhân viên chỉ đổi phòng sau khi được phê duyệt.
            @endif
        </p>
    </div>
    <a class="btn link" href="{{ $isEmployee ? route('employees.index') : route('departments.index') }}">Quay lại</a>
</div>

@if(session('success'))
    <div class="alert" style="background:#e6f4ea;border-left:4px solid #137333;padding:0.75rem 1rem;margin-bottom:1rem;">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert" style="background:#ffebee;border-left:4px solid #f44336;padding:0.75rem 1rem;margin-bottom:1rem;">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="alert">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card">
    <p><strong>{{ $isEmployee ? 'Nhân viên' : 'Phòng ban' }}:</strong> {{ $label }}</p>
    @if($isEmployee)
        <p class="muted">Email: {{ $employee->email }} @if($employee->user) · Tài khoản: {{ $employee->user->email }} (Admin sẽ xóa tài khoản sau khi Giám đốc duyệt)@endif</p>
    @endif
</div>

@if(! $isEmployee)
    <div class="card" style="margin-top:16px;">
        <h3 class="section-title" style="margin-top:0;">Nhân viên trong phòng ban ({{ $staff->count() }})</h3>
        @if($hasStaff)
            <p class="muted">Chọn phòng đích, nhập lý do hoặc đính kèm biên bản, rồi gửi Giám đốc duyệt chuyển. Hoặc đề nghị xóa từng người. Không gửi được đề nghị xóa phòng ban khi còn nhân viên.</p>
            @if($targets->isEmpty())
                <p class="muted">Chưa có phòng ban khác để chuyển. Hãy tạo phòng ban mới hoặc đề nghị xóa từng nhân viên.</p>
                <table>
                    <thead>
                        <tr>
                            <th>Mã NV</th>
                            <th>Họ tên</th>
                            <th>Email</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($staff as $member)
                            <tr>
                                <td><code>{{ $member->employee_code ?: '—' }}</code></td>
                                <td>{{ $member->name }}</td>
                                <td>{{ $member->email }}</td>
                                <td>
                                    @php
                                        $pendingId = $pendingStaff[$member->id] ?? null;
                                        $pendingTransferId = $pendingTransfers[$member->id] ?? null;
                                    @endphp
                                    @if($pendingId)
                                        <a href="{{ route('deletion_requests.show', $pendingId) }}">Chờ GĐ duyệt xóa</a>
                                    @elseif($pendingTransferId)
                                        <a href="{{ route('deletion_requests.show', $pendingTransferId) }}">Chờ GĐ duyệt chuyển</a>
                                    @else
                                        <a href="{{ route('deletion_requests.create_employee', $member) }}">Đề nghị xóa</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <form method="POST" action="{{ route('deletion_requests.transfer_employees', $department) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="field">
                        <label>Chuyển sang phòng ban</label>
                        <select name="target_department_id" required>
                            <option value="">— Chọn phòng ban —</option>
                            @foreach($targets as $target)
                                <option value="{{ $target->id }}" @selected((string) old('target_department_id') === (string) $target->id)>
                                    [{{ $target->code }}] {{ $target->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label>Lý do chuyển</label>
                        <textarea name="reason" rows="3" placeholder="Ví dụ: tái cơ cấu, giải thể phòng ban...">{{ old('reason') }}</textarea>
                    </div>
                    <div class="field">
                        <label>Biên bản / tài liệu (nếu có)</label>
                        <input type="file" name="document" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        <p class="muted">Cần lý do hoặc file. Giám đốc nhận thông báo và duyệt rồi nhân viên mới đổi phòng.</p>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th style="width:40px"><input type="checkbox" onclick="document.querySelectorAll('[data-staff]').forEach(el => el.checked = this.checked)"></th>
                                <th>Mã NV</th>
                                <th>Họ tên</th>
                                <th>Email</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($staff as $member)
                                @php
                                    $pendingId = $pendingStaff[$member->id] ?? null;
                                    $pendingTransferId = $pendingTransfers[$member->id] ?? null;
                                    $busy = $pendingId || $pendingTransferId;
                                @endphp
                                <tr>
                                    <td><input type="checkbox" data-staff name="employee_ids[]" value="{{ $member->id }}" @disabled($busy)></td>
                                    <td><code>{{ $member->employee_code ?: '—' }}</code></td>
                                    <td>{{ $member->name }}</td>
                                    <td>{{ $member->email }}</td>
                                    <td>
                                        @if($pendingId)
                                            <a href="{{ route('deletion_requests.show', $pendingId) }}">Chờ GĐ duyệt xóa</a>
                                        @elseif($pendingTransferId)
                                            <a href="{{ route('deletion_requests.show', $pendingTransferId) }}">Chờ GĐ duyệt chuyển</a>
                                        @else
                                            <a href="{{ route('deletion_requests.create_employee', $member) }}">Đề nghị xóa</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="actions" style="margin-top:12px;">
                        <button class="btn" type="submit" data-confirm="Gửi Giám đốc duyệt chuyển nhân viên đã chọn? Nhân viên chưa đổi phòng.">Gửi GĐ duyệt chuyển đã chọn</button>
                        <button class="btn primary" type="submit" name="transfer_all" value="1" data-confirm="Gửi Giám đốc duyệt chuyển toàn bộ nhân viên? Nhân viên chưa đổi phòng.">Gửi GĐ duyệt chuyển tất cả</button>
                    </div>
                </form>
            @endif
        @else
            <p>Phòng ban không còn nhân viên. Có thể gửi đề nghị xóa cho Giám đốc.</p>
        @endif
    </div>
@endif

@if($isEmployee || ! $hasStaff)
<div class="card" style="margin-top:16px;">
    <form method="POST" action="{{ $action }}" enctype="multipart/form-data">
        @csrf
        <div class="field">
            <label>Lý do xóa</label>
            <textarea name="reason" rows="4" placeholder="Ví dụ: nghỉ việc, tái cơ cấu...">{{ old('reason') }}</textarea>
        </div>
        <div class="field">
            <label>Biên bản / tài liệu đính kèm</label>
            <input type="file" name="document" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
            <p class="muted">PDF, Word hoặc ảnh. Tối đa 10MB. Cần ít nhất lý do hoặc file.</p>
        </div>
        <div class="actions">
            <button class="btn primary" type="submit" data-confirm="Gửi đề nghị này cho Giám đốc duyệt? Hồ sơ chưa bị xóa.">Gửi Giám đốc duyệt xóa</button>
        </div>
    </form>
</div>
@endif
@endsection
