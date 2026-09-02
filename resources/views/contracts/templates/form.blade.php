@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">{{ $isEdit ? 'Chỉnh sửa mẫu hợp đồng' : 'Tạo mẫu hợp đồng' }}</h2>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('contract-templates.index') }}">Quay lại</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ $isEdit ? route('contract-templates.update', $template) : route('contract-templates.store') }}" id="templateForm">
                @csrf
                @if($isEdit)
                    @method('PUT')
                @endif

                <div class="mb-3">
                    <label class="form-label">Tiêu đề</label>
                    <input type="text" name="title" class="form-control" value="{{ old('title', $template->title) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Loại hợp đồng</label>
                    <select name="contract_type" class="form-select" required id="contractTypeSelect">
                        <option value="">-- Chọn loại hợp đồng --</option>
                        <option value="internship" {{ old('contract_type', $template->contract_type) == 'internship' ? 'selected' : '' }}>Thực tập</option>
                        <option value="probation" {{ old('contract_type', $template->contract_type) == 'probation' ? 'selected' : '' }}>Thử việc</option>
                        <option value="fixed_term" {{ old('contract_type', $template->contract_type) == 'fixed_term' ? 'selected' : '' }}>Lao động xác định thời hạn</option>
                        <option value="indefinite" {{ old('contract_type', $template->contract_type) == 'indefinite' ? 'selected' : '' }}>Lao động không xác định thời hạn</option>
                        <option value="official" {{ old('contract_type', $template->contract_type) == 'official' ? 'selected' : '' }}>Lao động chính thức</option>
                        <option value="seasonal" {{ old('contract_type', $template->contract_type) == 'seasonal' ? 'selected' : '' }}>Hợp đồng thời vụ</option>
                    </select>
                </div>

                <!-- Standard Clauses Section -->
                <div class="mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <h5 class="mb-0">Điều khoản chuẩn</h5>
                    </div>
                    
                    <div class="alert alert-info mb-3" id="clausesInfo">
                        <small>Vui lòng chọn loại hợp đồng để xem các điều khoản chuẩn</small>
                    </div>

                    <div id="clausesContainer" style="display: none;">
                        <div class="list-group mb-3" id="clausesList" style="max-height: 500px; overflow-y: auto;">
                            <!-- Clauses will be loaded here -->
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nội dung tự do (Chỉnh sửa/Bổ sung)</label>
                    <textarea name="content" class="form-control" rows="15" id="contentTextarea" placeholder="Nội dung sẽ được tự động tạo từ các điều khoản chuẩn đã chọn. Bạn có thể chỉnh sửa tại đây.">{{ old('content', $template->content) }}</textarea>
                </div>

                <div class="mb-3">
                    <button type="button" class="btn btn-outline-primary btn-sm" id="generateContentBtn">
                        <i class="fas fa-sync-alt"></i> Tạo lại nội dung từ điều khoản đã chọn
                    </button>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Mặc định</label>
                        <select name="is_default" class="form-select">
                            <option value="0" {{ old('is_default', (int) $template->is_default) == 0 ? 'selected' : '' }}>Không</option>
                            <option value="1" {{ old('is_default', (int) $template->is_default) == 1 ? 'selected' : '' }}>Có</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Trạng thái</label>
                        <select name="status" class="form-select">
                            <option value="active" {{ old('status', $template->status) == 'active' ? 'selected' : '' }}>Hoạt động</option>
                            <option value="inactive" {{ old('status', $template->status) == 'inactive' ? 'selected' : '' }}>Không hoạt động</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Lưu</button>
                    <a href="{{ route('contract-templates.index') }}" class="btn btn-outline-secondary">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.clause-item {
    padding: 12px;
    border-left: 3px solid var(--primary);
    transition: all 0.2s;
}

.clause-item:hover {
    background: var(--panel);
}

.clause-item input[type="checkbox"] {
    margin-right: 8px;
}

.clause-title {
    font-weight: 500;
    color: var(--text);
    margin-bottom: 4px;
}

.clause-content {
    font-size: 0.9em;
    color: var(--muted);
    margin-top: 4px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const contractTypeSelect = document.getElementById('contractTypeSelect');
    const clausesList = document.getElementById('clausesList');
    const clausesContainer = document.getElementById('clausesContainer');
    const clausesInfo = document.getElementById('clausesInfo');
    const contentTextarea = document.getElementById('contentTextarea');
    const generateContentBtn = document.getElementById('generateContentBtn');

    const clauses = @json($clauses ?? []);

    function loadClauses() {
        const contractType = contractTypeSelect.value;
        
        if (!contractType) {
            clausesContainer.style.display = 'none';
            clausesInfo.innerHTML = '<small>Vui lòng chọn loại hợp đồng để xem các điều khoản chuẩn</small>';
            return;
        }

        const typeClauses = clauses.filter(c => c.contract_type === contractType);
        
        if (typeClauses.length === 0) {
            clausesInfo.innerHTML = '<small class="text-warning">Không có điều khoản chuẩn cho loại hợp đồng này</small>';
            clausesContainer.style.display = 'none';
            return;
        }

        clausesContainer.style.display = 'block';
        clausesInfo.innerHTML = `<small class="text-success">Có ${typeClauses.length} điều khoản chuẩn cho loại hợp đồng này</small>`;

        clausesList.innerHTML = typeClauses.map(clause => `
            <div class="clause-item list-group-item">
                <div class="form-check">
                    <input class="form-check-input clause-check" type="checkbox" id="clause-${clause.id}" 
                           data-clause-id="${clause.id}" data-section="${clause.section_number}" 
                           data-title="${clause.section_title}" data-content="${clause.content.replace(/"/g, '&quot;')}"
                           ${clause.is_mandatory ? 'checked disabled' : ''}>
                    <label class="form-check-label w-100" for="clause-${clause.id}">
                        <div class="clause-title">
                            <strong>${clause.section_number}</strong> - ${clause.section_title}
                            ${clause.is_mandatory ? '<span class="badge bg-danger ms-2">Bắt buộc</span>' : ''}
                        </div>
                        <div class="clause-content">${clause.content.substring(0, 150)}${clause.content.length > 150 ? '...' : ''}</div>
                    </label>
                </div>
            </div>
        `).join('');

        // Attach change listeners
        document.querySelectorAll('.clause-check').forEach(checkbox => {
            checkbox.addEventListener('change', generateContent);
        });
    }

    function generateContent() {
        const checkedBoxes = document.querySelectorAll('.clause-check:checked');
        const selectedClauses = Array.from(checkedBoxes).map(box => ({
            section: box.dataset.section,
            title: box.dataset.title,
            content: box.dataset.content
        })).sort((a, b) => parseFloat(a.section) - parseFloat(b.section));

        const content = selectedClauses.map(clause => 
            `${clause.section}. ${clause.title}\n${clause.content}\n`
        ).join('\n');

        contentTextarea.value = content;
    }

    contractTypeSelect.addEventListener('change', loadClauses);
    generateContentBtn.addEventListener('click', generateContent);

    // Load clauses on page load if contract type is selected
    if (contractTypeSelect.value) {
        loadClauses();
    }
});
</script>
@endsection
