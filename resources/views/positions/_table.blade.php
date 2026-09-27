@isset($title)
    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:8px;">
        <h2 style="font-size:17px; margin:0;">
            {{ $title }}
            @isset($deptLink)
            @endisset
        </h2>
        <span class="badge bg-secondary">{{ $positions->count() }} chức vụ</span>
    </div>
@endisset

<div class="position-cards">
    @foreach ($positions as $p)
        <article class="position-card">
            <div class="position-card-head">
                <div style="display:flex;gap:10px;align-items:flex-start;">
                    <span class="position-card-index">{{ $loop->iteration }}</span>
                    <div>
                        <h2 class="position-card-title">{{ $p->name }}</h2>
                        <p class="position-card-level">Cấp bậc: {{ $p->level ?: '—' }}</p>
                    </div>
                </div>
                <i class="bi bi-briefcase" style="color:#2563eb;font-size:20px;"></i>
            </div>
            <div class="position-card-field">
                <span class="position-card-label">Lương cơ bản</span>
                <div class="position-card-value">{{ number_format($p->base_salary, 0, ',', '.') }} đ</div>
            </div>
            <div class="position-card-field">
                <span class="position-card-label">Khoảng lương</span>
                <div class="position-card-value">{{ number_format($p->salary_range_min, 0, ',', '.') }} – {{ number_format($p->salary_range_max, 0, ',', '.') }} đ</div>
            </div>
            <div class="position-card-field">
                <span class="position-card-label">Nhân viên</span>
                <div class="position-card-value">{{ $p->employees->count() }}</div>
            </div>
            <div class="position-card-field">
                <span class="position-card-label">Mô tả</span>
                <div class="position-card-value">{{ $p->description ?: '-' }}</div>
            </div>
        </article>
    @endforeach
</div>