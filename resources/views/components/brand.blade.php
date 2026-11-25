{{--
    PharmaTrack logo: mark + "PharmaTrack" wordmark, identical for admin, pharmacist, patient and public pages.
    theme="dark" for dark backgrounds (sidebars, login panel), "light" for white backgrounds.
--}}
@props(['size' => 36, 'theme' => 'light', 'tagline' => null])
@php
    $dark = $theme === 'dark';
    $textColor = $dark ? '#ffffff' : '#1e293b';
    $accent = $dark ? '#2dd4bf' : '#0d9488';
    $fontSize = round($size * 0.6);
@endphp
<span {{ $attributes }} style="display:inline-flex;align-items:center;gap:{{ round($size * 0.3) }}px;">
    <x-brand-mark :size="$size" />
    <span style="display:flex;flex-direction:column;line-height:1.1;">
        <span style="font-size:{{ $fontSize }}px;font-weight:800;letter-spacing:-0.02em;color:{{ $textColor }};">Pharma<span style="color:{{ $accent }};">Track</span></span>
        @if ($tagline)
            <span style="margin-top:3px;font-size:{{ max(10, round($size * 0.28)) }}px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:{{ $dark ? '#99f6e4' : $accent }};">{{ $tagline }}</span>
        @endif
    </span>
</span>
