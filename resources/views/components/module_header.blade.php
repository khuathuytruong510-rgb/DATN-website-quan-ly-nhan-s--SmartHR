@props(['title' => '', 'subtitle' => '', 'buttonText' => null, 'buttonRoute' => null])

<div class="module-header card shadow-sm mb-3">
    <div class="card-body d-flex justify-content-between align-items-center module-header-body py-3">
        <div>
            <h3 class="mb-1 fw-bold module-title">{{ $title }}</h3>
            @if($subtitle)
                <small class="text-white-50 module-subtitle">{{ $subtitle }}</small>
            @endif
        </div>

        @if($buttonText && $buttonRoute)
            <a href="{{ $buttonRoute }}" class="btn module-primary-btn">{{ $buttonText }}</a>
        @endif
    </div>
</div>

<style>
.module-header { background: linear-gradient(90deg, #2563eb 0%, #4f46e5 100%); border-radius: 14px; }
.module-header-body { color: #fff; gap: 16px; }
.module-title { color: #fff; margin: 0 0 6px; }
.module-subtitle { color: rgba(255,255,255,0.88); }
.module-primary-btn { background: #fff; color: #1d4ed8; border: 1px solid transparent; }
.module-primary-btn:hover { background: #eff6ff; color: #1e40af; }
</style>