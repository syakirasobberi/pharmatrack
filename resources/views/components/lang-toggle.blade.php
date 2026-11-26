{{--
    EN / BM switch. Inline styles on purpose: pages using the prebuilt Vite CSS
    would otherwise need a rebuild for any new Tailwind class.
--}}
@php
    $isMs = app()->getLocale() === 'ms';
    $on  = 'background:#0d9488;color:#fff;';
    $off = 'color:#64748b;';
    $btn = 'min-width:42px;padding:6px 10px;border-radius:8px;text-align:center;font-size:12px;font-weight:800;letter-spacing:.04em;text-decoration:none;line-height:1.2;';
@endphp
<div {{ $attributes }} role="group" aria-label="{{ __('Language') }}"
     style="display:inline-flex;gap:2px;padding:4px;border:1px solid #e2e8f0;border-radius:12px;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.05);">
    <a href="{{ route('locale.switch', 'en') }}" lang="en" hreflang="en" @if (!$isMs) aria-current="true" @endif style="{{ $btn }}{{ $isMs ? $off : $on }}">EN</a>
    <a href="{{ route('locale.switch', 'ms') }}" lang="ms" hreflang="ms" @if ($isMs) aria-current="true" @endif style="{{ $btn }}{{ $isMs ? $on : $off }}">BM</a>
</div>
