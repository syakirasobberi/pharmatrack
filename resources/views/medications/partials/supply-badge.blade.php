{{-- Supply / refill status for one medication. $medication (App\Models\Medication) --}}
@php
    $refill = $medication->refillStatus();
    $daysLeft = $medication->supplyDaysLeft();
@endphp
@if($medication->supply_until)
    <span class="inline-flex flex-wrap items-center gap-1 text-xs">
        <span class="font-semibold text-slate-600">{{ __('Supply until :date', ['date' => $medication->supply_until->translatedFormat('d M Y')]) }}</span>
        @if($refill === 'overdue')
            <span class="px-2 py-0.5 rounded-full bg-red-100 text-red-700 border border-red-200 font-bold">{{ trans_choice('Ran out :count day ago|Ran out :count days ago', abs($daysLeft), ['count' => abs($daysLeft)]) }}</span>
        @elseif($refill === 'due')
            <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-200 font-bold">{{ $daysLeft === 0 ? __('Runs out today') : trans_choice(':count day left|:count days left', $daysLeft, ['count' => $daysLeft]) }}</span>
        @endif
    </span>
@endif
@if($medication->refill_requested_at && $medication->isCurrent())
    <span class="mt-1 inline-flex px-2 py-0.5 rounded-full bg-teal-100 text-teal-700 border border-teal-200 text-xs font-bold">{{ __('Refill requested :date', ['date' => $medication->refill_requested_at->translatedFormat('d M')]) }}</span>
@endif
