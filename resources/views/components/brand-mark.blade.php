{{--
    PharmaTrack logo mark: the teal cross tile. One source for every page and role.
    Styled inline so it looks the same on pages built with Vite, the Tailwind CDN or plain CSS.
--}}
@props(['size' => 40])
@php
    $size = (int) $size;
    $radius = (int) round($size * 0.28);
    $icon = (int) round($size * 0.55);
@endphp
<span {{ $attributes }} aria-hidden="true"
      style="display:inline-flex;flex-shrink:0;align-items:center;justify-content:center;width:{{ $size }}px;height:{{ $size }}px;border-radius:{{ $radius }}px;background:linear-gradient(135deg,#14b8a6,#06b6d4);color:#fff;box-shadow:0 4px 12px rgba(20,184,166,.3);">
    <svg width="{{ $icon }}" height="{{ $icon }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linejoin="round">
        <path d="M10 4h4v6h6v4h-6v6h-4v-6H4v-4h6V4z"/>
    </svg>
</span>
