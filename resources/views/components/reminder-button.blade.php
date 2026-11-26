{{--
    "Send Check-up Reminder" action, shared by the patient profile and the dashboard Clinical Alerts.
    size="md" = primary button (profile header), size="sm" = compact outline button (alert cards).
    Shows a "Sending…" state so a double-click cannot send two emails.
--}}
@props(['patient', 'size' => 'md', 'label' => null])
@php
    $compact = $size === 'sm';
    $label = $label ?? ($compact ? __('Send Reminder') : __('Send Check-up Reminder'));
    $classes = $compact
        ? 'w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-white border border-teal-300 text-teal-700 text-xs font-semibold rounded-lg shadow-sm hover:bg-teal-50 hover:border-teal-400 focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-teal-500'
        : 'w-full inline-flex justify-center items-center gap-2 px-4 py-2.5 bg-teal-600 border border-transparent text-white font-semibold text-sm rounded-xl shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500';
    $icon = $compact ? 'w-3.5 h-3.5' : 'w-4 h-4';
@endphp
<form action="{{ route('pharmacist.patients.sendReminder', $patient->id) }}" method="POST"
      {{ $attributes->merge(['class' => 'inline-flex']) }}
      x-data="{ sending: false }" @submit="sending = true">
    @csrf
    <button type="submit" :disabled="sending"
            title="{{ __('Sends :name a check-up reminder by email and in-app notification', ['name' => $patient->user?->name ?? __('the patient')]) }}"
            class="{{ $classes }} disabled:opacity-70 disabled:cursor-wait transition-all">
        <svg x-show="!sending" class="{{ $icon }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
        <svg x-show="sending" x-cloak class="{{ $icon }} animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
        <span x-text="sending ? @js(__('Sending…')) : @js($label)">{{ $label }}</span>
    </button>
</form>
