@props(['class' => ''])

<footer {{ $attributes->merge(['class' => 'footer ' . $class]) }}>
    @if ($slot->isEmpty())
        &copy; {{ date('Y') }} PharmaTrack &mdash; Smart Pharmacy System.
    @else
        {{ $slot }}
    @endif
</footer>
