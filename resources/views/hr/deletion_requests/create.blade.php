@extends('layouts.app')

@section('title', 'Yêu cầu xóa')

@section('content')
@php
    $kindLabel = $kind === 'employee' ? 'nhân viên' : 'phòng ban';
@endphp
<div class="content" style="max-width:760px;">
    <div class="page-head">
        <div>
            <h1>Yêu cầu xóa {{ $kindLabel }}</h1>
            <p class="muted">Quy trình: HR gửi yêu cầu → Giám đốc duyệt → HR thực hiện xóa.</p>
        </div>
        <a class="btn link" href="{{ $kind === 'employee' ? route('employees.index') : route('departments.index') }}">← Quay lại</a>
    </div>

    <div class="card" style="padding:0;overflow:hidden;margin-bottom:16px;">
        <table>
            <tbody>
                <tr>
                    <th style="width:220px;background:#f8fafc;">Đối tượng</th>
                    <td>{{ ucfirst($kindLabel) }}</td>
                </tr>
                @if ($kind === 'employee')
                    <tr>
                        <th style="background:#f8fafc;">Họ tên</th>
                        <td><strong>{{ $target->name }}</strong></td>
                    </tr>
                    <tr>
                        <th style="background:#f8fafc;">Mã NV</th>
                        <td><code>{{ $target->employee_code ?? '—' }}</code></td>
                    </tr>
                    <tr>
                        <th style="background:#f8fafc;">Chức vụ</th>
                        <td>{{ $target->position ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th style="background:#f8fafc;">Phòng ban</th>
                        <td>{{ $target->department ? '[' . $target->department->code . '] ' . $target->department->name : '—' }}</td>
                    </tr>
                    <tr>
                        <th style="background:#f8fafc;">Trạng thái</th>
                        <td>{{ ucfirst($target->status ?? '—') }}</td>
                    </tr>
                @else
                    <tr>
                        <th style="background:#f8fafc;">Tên phòng ban</th>
                        <td><strong>{{ $target->name }}</strong> <span class="badge">{{ $target->code }}</span></td>
                    </tr>
                    <tr>
                        <th style="background:#f8fafc;">Số nhân viên</th>
                        <td>{{ $target->employees()->count() }}</td>
                    </tr>
                    <tr>
                        <th style="background:#f8fafc;">Mô tả</th>
                        <td>{{ $target->description ?: '—' }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="card">
        <form method="POST" action="{{ route('deletion_requests.store') }}">
            @csrf
            <input type="hidden" name="kind" value="{{ $kind }}">
            <input type="hidden" name="target" value="{{ $target->id }}">

            <div class="field">
                <label for="reason">Lý do xóa <span style="color:#dc2626;">*</span></label>
                <textarea id="reason" name="reason" rows="5" class="form-control" required maxlength="2000" placeholder="Nhập lý do đề nghị xóa...">{{ old('reason') }}</textarea>
                @error('reason')<span class="error">{{ $message }}</span>@enderror
            </div>

            <div class="actions">
                <button class="btn danger" type="submit" data-confirm="Gửi yêu cầu xóa tới Giám đốc duyệt?">Gửi yêu cầu xóa</button>
                <a class="btn" href="{{ $kind === 'employee' ? route('employees.index') : route('departments.index') }}">Hủy</a>
            </div>
        </form>
    </div>
</div>
@endsection