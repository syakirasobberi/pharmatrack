{{-- Supply / refill status for one medication. $medication (App\Models\Medication) --}}
@php
    $refill = $medication->refillStatus();
    $daysLeft = $medication->supplyDaysLeft();
@endphp
@if($medication->supply_until)
    <span class="inline-flex flex-wrap items-center gap-1 text-xs">
        <span class="font-semibold text-slate-600">Supply until {{ $medication->supply_until->format('d M Y') }}</span>
        @if($refill === 'overdue')
            <span class="px-2 py-0.5 rounded-full bg-red-100 text-red-700 border border-red-200 font-bold">Ran out {{ abs($daysLeft) }} {{ \Illuminate\Support\Str::plural('day', abs($daysLeft)) }} ago</span>
        @elseif($refill === 'due')
            <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 border border-amber-200 font-bold">{{ $daysLeft === 0 ? 'Runs out today' : $daysLeft . ' ' . \Illuminate\Support\Str::plural('day', $daysLeft) . ' left' }}</span>
        @endif
    </span>
@endif
@if($medication->refill_requested_at && $medication->isCurrent())
    <span class="mt-1 inline-flex px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-bold">Refill requested {{ $medication->refill_requested_at->format('d M') }}</span>
@endif
