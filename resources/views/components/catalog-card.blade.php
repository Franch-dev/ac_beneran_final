@php
    $href = $href ?? null;
    $title = $title ?? '';
    $desc = $desc ?? '';
    $domain = $domain ?? '';
    $tag = $tag ?? '';
    $footerText = $footerText ?? '';

    $thumbStyle = $thumbStyle ?? null;
    $iconStyle = $iconStyle ?? null;
    $iconClass = $iconClass ?? 'fas fa-circle';

    $buttonAttrs = $buttonAttrs ?? [];
    // $asButton:
    // - false/null => render <a>
    // - true => render <button>
    $asButton = $asButton ?? false;
@endphp

@if(!$asButton && $href)
    <a href="{{ $href }}" class="catalog-card">
        @include('components.catalog-card-inner', [
            'thumbStyle' => $thumbStyle,
            'iconStyle' => $iconStyle,
            'iconClass' => $iconClass,
            'domain' => $domain,
            'tag' => $tag,
            'title' => $title,
            'desc' => $desc,
            'footerText' => $footerText,
        ])
    </a>
@else
    @php
        // Convert attributes array to HTML string
        $attrs = '';
        if (is_array($buttonAttrs)) {
            foreach ($buttonAttrs as $k => $v) {
                $attrs .= ' ' . $k . '="' . e((string)$v) . '"';
            }
        }
    @endphp
    <button type="button" class="catalog-card catalog-card-button" {!! $attrs !!}>
        @include('components.catalog-card-inner', [
            'thumbStyle' => $thumbStyle,
            'iconStyle' => $iconStyle,
            'iconClass' => $iconClass,
            'domain' => $domain,
            'tag' => $tag,
            'title' => $title,
            'desc' => $desc,
            'footerText' => $footerText,
        ])
    </button>
@endif

