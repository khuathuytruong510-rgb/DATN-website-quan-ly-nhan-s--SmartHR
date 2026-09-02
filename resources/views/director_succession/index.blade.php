@extends('layouts.app')

@section('title', 'Người giữ chức Giám đốc')

@section('content')
    @php
        $defaultEffective = old('effective_on', collect([now()->toDateString(), $minEffectiveOn ?? null])->filter()->max());
        $oldOutgoingStatus = old('outgoing_status', 'active');
        $showNewPosition = $oldOutgoingStatus === 'active';
    @endphp

    <div class="page-head">
        <div>
            <p class="eyebrow">Quản trị chức vụ</p>
            <h1>Cập nhật người giữ chức Giám đốc</h1>
            <p class="page-lead muted">Bổ nhiệm/miễn nhiệm do doanh nghiệp quyết định ngoài SmartHR. Admin chỉ cập nhật người đang giữ chức, tài khoản và role — không đổi tên tài khoản cũ, không xóa lịch sử phê duyệt.</p>
        </div>
        <div class="page-actions">
            <a class="btn" href="{{ route('accounts.index') }}">Quản lý tài khoản</a>
        </div>
    </div>

    <div class="callout info">
        <p class="callout-title">Phạm vi hệ thống</p>
        <ul>
            <li>Quyết định thay Giám đốc / bổ nhiệm Giám đốc mới: <strong>ngoài SmartHR</strong>.</li>
            <li>Admin cập nhật: hồ sơ người giữ chức, tài khoản, role Director, trạng thái, lịch sử nhiệm kỳ.</li>
            <li>Tại một thời điểm chỉ có <strong>01 người</strong> giữ chức Giám đốc. Quyền đi theo người đang giữ chức.</li>
            <li>Không xóa dữ liệu Giám đốc cũ. Bảng lương / nghiệp vụ đã duyệt giữ nguyên người duyệt tại thời điểm đó.</li>
        </ul>
    </div>

    @if($currentDirectors->count() > 1)
        <div class="callout warn">
            <p class="callout-title">Đang có hơn một tài khoản role Director</p>
            <p>Hãy cập nhật người giữ chức bên dưới — hệ thống sẽ thu hồi quyền của người cũ và chỉ giữ 01 Giám đốc.</p>
        </div>
    @endif

    <div class="grid two-cols">
        @forelse($currentDirectors as $director)
            @php
                $employee = $director->employee;
                $tenure = $tenures[$director->id] ?? null;
            @endphp
            <div class="card">
                <div class="card-head">
                    <p class="eyebrow">Đang giữ chức</p>
                    <h2 class="card-title">{{ $director->name }}</h2>
                </div>
                <div class="meta-list">
                    <div>
                        <label>Chức vụ</label>
                        <div>{{ $employee->position ?? 'Giám đốc' }}</div>
                    </div>
                    <div>
                        <label>Tài khoản</label>
                        <div>{{ $director->email }}</div>
                    </div>
                    <div>
                        <label>Vai trò hệ thống</label>
                        <div><span class="badge director">Giám đốc</span></div>
                    </div>
                    <div>
                        <label>Phòng ban</label>
                        <div>{{ optional($employee?->department)->name ?? 'Ban Giám đốc' }}</div>
                    </div>
                    <div>
                        <label>Trạng thái</label>
                        <div>Đang giữ chức vụ / Đang hoạt động</div>
                    </div>
                    <div>
                        <label>Thời gian giữ chức</label>
                        <div>{{ $tenure?->tenureLabel() ?? '—' }}</div>
                    </div>
                </div>
                <div class="org-tree">
                    <p class="org-tree-label">Cơ cấu hiện tại</p>
                    <div class="org-tree-unit">Ban Giám đốc</div>
                    <div class="org-tree-person">{{ $director->name }} — Giám đốc</div>
                </div>
            </div>
        @empty
            <div class="card">
                <div class="card-head">
                    <h2 class="card-title">Chưa có Giám đốc</h2>
                    <p class="card-lead">Chưa có tài khoản nào đang có role Director. Có thể bổ nhiệm Giám đốc đầu tiên từ form bên cạnh.</p>
                </div>
            </div>
        @endforelse

        <div class="card">
            <div class="card-head">
                <h2 class="card-title">Cập nhật theo quyết định</h2>
                <p class="card-lead">Người trong công ty: chọn trong danh sách. Người từ bên ngoài: HR tạo hồ sơ trước, Admin tạo tài khoản, rồi mới chọn ở đây. Không đổi tên tài khoản Giám đốc cũ.</p>
            </div>

            <div class="case-grid">
                <div class="case-item"><strong>Trường hợp A</strong> — người đã có trong SmartHR: chọn trong ô bên dưới. Hệ thống đổi chức vụ / role và lưu nhiệm kỳ.</div>
                <div class="case-item"><strong>Trường hợp B</strong> — người hoàn toàn mới: chưa có bản ghi thì không chọn được. HR tạo hồ sơ, Admin kết nối tài khoản, rồi quay lại chọn.</div>
            </div>

            @if(($unlinkedEmployees ?? collect())->isNotEmpty())
                <div class="callout warn">
                    <p class="callout-title">Hồ sơ đã có, chưa có tài khoản</p>
                    <p>HR đã tạo nhân sự. Admin tạo tài khoản rồi người đó mới xuất hiện trong danh sách.</p>
                    <ul>
                        @foreach($unlinkedEmployees as $unlinked)
                            <li>
                                {{ $unlinked->name }}
                                @if($unlinked->employee_code) <code>{{ $unlinked->employee_code }}</code> @endif
                                — {{ $unlinked->position ?: '—' }}
                                @if($unlinked->department)
                                    · {{ $unlinked->department->name }}
                                @endif
                                <a href="{{ route('accounts.create', ['employee' => $unlinked->id]) }}">Tạo tài khoản</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('director_succession.store') }}" id="director-succession-form" class="form-stack">
                @csrf
                <div class="field">
                    <label class="form-label" for="incoming_user_id">Người được bổ nhiệm làm Giám đốc</label>
                    <select id="incoming_user_id" name="incoming_user_id" class="form-select" required>
                        <option value="">Chọn nhân sự</option>
                        @foreach($candidates as $candidate)
                            <option value="{{ $candidate->id }}" {{ old('incoming_user_id') == $candidate->id ? 'selected' : '' }}>
                                {{ $candidate->name }} — {{ $candidate->email }}
                                @if($candidate->employee)
                                    ({{ $candidate->employee->position ?: 'Nhân viên' }}
                                    @if($candidate->employee->department)
                                        · {{ $candidate->employee->department->name }}
                                    @endif)
                                @endif
                            </option>
                        @endforeach
                    </select>
                    <p class="form-hint">
                        Chưa có trong danh sách?
                        <a href="{{ route('director_succession.prepare_new') }}">Tạo hồ sơ nhân sự mới</a>
                    </p>
                    @error('incoming_user_id')<span class="error">{{ $message }}</span>@enderror
                    @if($candidates->isEmpty())
                        <p class="form-hint">Chưa có nhân sự đã gắn tài khoản để chọn. Dùng “Tạo hồ sơ nhân sự mới” nếu người được bổ nhiệm đến từ bên ngoài.</p>
                    @endif
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="field">
                            <label class="form-label" for="effective_on">Ngày hiệu lực</label>
                            <input id="effective_on" class="form-control" name="effective_on" type="date" value="{{ $defaultEffective }}" @if($minEffectiveOn) min="{{ $minEffectiveOn }}" @endif required>
                            @if($minEffectiveOn)
                                <p class="form-hint">Không được sớm hơn nhiệm kỳ hiện tại. Ngày sớm nhất: {{ \Carbon\Carbon::parse($minEffectiveOn)->format('d/m/Y') }}.</p>
                            @endif
                            @error('effective_on')<span class="error">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="field">
                            <label class="form-label" for="decision_ref">Số quyết định (ngoài hệ thống)</label>
                            <input id="decision_ref" class="form-control" name="decision_ref" value="{{ old('decision_ref') }}" maxlength="100" placeholder="VD: QĐ-12/2026">
                            @error('decision_ref')<span class="error">{{ $message }}</span>@enderror
                        </div>
                    </div>
                </div>

                @if($currentDirectors->isNotEmpty())
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="field">
                            <label class="form-label" for="outgoing_role">Vai trò hệ thống sau khi chuyển giao</label>
                            <select id="outgoing_role" class="form-select" name="outgoing_role" required>
                                <option value="employee" {{ old('outgoing_role', 'employee') === 'employee' ? 'selected' : '' }}>Nhân viên — hết quyền Giám đốc</option>
                                <option value="hr" {{ old('outgoing_role') === 'hr' ? 'selected' : '' }}>HR</option>
                                <option value="accountant" {{ old('outgoing_role') === 'accountant' ? 'selected' : '' }}>Kế toán</option>
                            </select>
                            <p class="form-hint">Đây là role <strong>sau</strong> ngày hiệu lực, không phải role hiện tại. Người cũ sẽ mất quyền Giám đốc ngay khi chuyển giao.</p>
                            @error('outgoing_role')<span class="error">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="field">
                            <label class="form-label" for="outgoing_status">Trạng thái hồ sơ người cũ sau chuyển giao</label>
                            <select id="outgoing_status" class="form-select" name="outgoing_status" required>
                                <option value="active" {{ $oldOutgoingStatus === 'active' ? 'selected' : '' }}>Còn làm việc</option>
                                <option value="resigned" {{ $oldOutgoingStatus === 'resigned' ? 'selected' : '' }}>Nghỉ việc</option>
                                <option value="on_leave" {{ $oldOutgoingStatus === 'on_leave' ? 'selected' : '' }}>Tạm nghỉ</option>
                            </select>
                            @error('outgoing_status')<span class="error">{{ $message }}</span>@enderror
                        </div>
                    </div>
                </div>

                <div class="field" id="outgoing-position-field" @if(! $showNewPosition) hidden @endif>
                    <label class="form-label" for="outgoing_position">Chức vụ mới của người cũ</label>
                    <input id="outgoing_position" class="form-control" name="outgoing_position" list="position-options" value="{{ old('outgoing_position', $showNewPosition ? 'Nhân viên' : '') }}" @if($showNewPosition) required @endif>
                    <datalist id="position-options">
                        @foreach($positions as $position)
                            <option value="{{ $position->name }}"></option>
                        @endforeach
                    </datalist>
                    <p class="form-hint">Chỉ nhập khi người cũ còn làm việc sau khi thôi chức Giám đốc.</p>
                    @error('outgoing_position')<span class="error">{{ $message }}</span>@enderror
                </div>
                @else
                    <input type="hidden" name="outgoing_role" value="employee">
                    <input type="hidden" name="outgoing_status" value="active">
                @endif

                <div class="field">
                    <label class="form-label" for="note">Ghi chú</label>
                    <textarea id="note" class="form-control" name="note" rows="3">{{ old('note') }}</textarea>
                </div>

                <div class="actions">
                    <button class="btn primary" type="submit" data-confirm="Xác nhận: thu hồi quyền Giám đốc của người cũ, cấp quyền cho người mới, giữ nguyên lịch sử phê duyệt?">Cập nhật người giữ chức</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <h2 class="card-title">Lịch sử nhiệm kỳ Giám đốc</h2>
            <p class="card-lead">Mỗi dòng là một nhiệm kỳ. Người đã thôi chức vẫn giữ nguyên các nghiệp vụ đã phê duyệt khi còn quyền Giám đốc — hệ thống không chuyển người duyệt sang Giám đốc mới.</p>
        </div>
        @if($histories->isEmpty())
            <div class="empty">Chưa có lịch sử nhiệm kỳ.</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Người giữ chức</th>
                        <th>Tài khoản</th>
                        <th>Chức vụ</th>
                        <th>Thời gian giữ chức</th>
                        <th>Trạng thái</th>
                        <th>Quyết định</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($histories as $row)
                        <tr>
                            <td>{{ $row->holder_name }}</td>
                            <td>{{ $row->holder_email ?: '—' }}</td>
                            <td>{{ $row->position_name }}</td>
                            <td>{{ $row->tenureLabel() }}</td>
                            <td>{{ $row->statusLabel() }}</td>
                            <td>{{ $row->decision_ref ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="card">
        <div class="card-head">
            <h2 class="card-title">Nhật ký cập nhật người giữ chức</h2>
        </div>
        @if($logs->isEmpty())
            <div class="empty">Chưa có nhật ký thay Giám đốc.</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Thời gian</th>
                        <th>Admin thực hiện</th>
                        <th>Hành động</th>
                        <th>Chi tiết</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                        <tr>
                            <td>{{ optional($log->created_at)->format('d/m/Y H:i') }}</td>
                            <td>{{ optional($log->user)->name ?: '—' }}</td>
                            <td>{{ $log->label() }}</td>
                            <td>{{ $log->detail() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection

@push('scripts')
<script>
(function () {
    var status = document.getElementById('outgoing_status');
    var field = document.getElementById('outgoing-position-field');
    var input = document.getElementById('outgoing_position');
    if (!status || !field || !input) return;

    function sync() {
        var working = status.value === 'active';
        field.hidden = !working;
        input.required = working;
        if (!working) {
            input.value = '';
        } else if (!input.value) {
            input.value = 'Nhân viên';
        }
    }

    status.addEventListener('change', sync);
    sync();
})();
</script>
@endpush
