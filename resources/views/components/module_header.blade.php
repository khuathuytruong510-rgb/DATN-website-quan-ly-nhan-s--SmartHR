@props(['title' => '', 'subtitle' => '', 'buttonText' => null, 'buttonRoute' => null])

<div class="module-header card shadow-sm mb-4">
    <div class="card-body d-flex justify-content-between align-items-center module-header-body">
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
.module-header { background: linear-gradient(90deg, #2d9cff 0%, #3aa0ff 50%, #6fc3ff 100%); border-radius: .6rem; }
.module-header-body { color: #fff; }
.module-title { color: #fff; }
.module-subtitle { color: rgba(255,255,255,0.85); }
.module-primary-btn { background: #fff; color: #1967d2; border: none; }
</style>