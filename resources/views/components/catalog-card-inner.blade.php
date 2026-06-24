@php
    $thumbStyle = $thumbStyle ?? null;
    $iconStyle = $iconStyle ?? null;
    $iconClass = $iconClass ?? 'fas fa-circle';
    $domain = $domain ?? '';
    $tag = $tag ?? '';
    $title = $title ?? '';
    $desc = $desc ?? '';
    $footerText = $footerText ?? '';
@endphp

<div class="catalog-card-thumb" style="{{ $thumbStyle }}">
    <div class="catalog-thumb-icon" style="{{ $iconStyle }}">
        <i class="{{ $iconClass }}"></i>
    </div>
</div>

<div class="catalog-card-body">
    <div class="catalog-card-meta">
        <span class="catalog-domain">{{ $domain }}</span>
        <span class="catalog-tag">{{ $tag }}</span>
    </div>
    <h3 class="catalog-card-title">{{ $title }}</h3>
    <p class="catalog-card-desc">{{ $desc }}</p>
</div>

<div class="catalog-card-footer">
    <span class="catalog-visit">{!! $footerText !!}</span>
</div>

