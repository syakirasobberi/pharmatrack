{{-- Last 7 days of logged doses. $last7 from App\Support\Adherence::forMedication() --}}
<div class="flex items-end gap-1" aria-label="Doses logged in the last 7 days: {{ collect($last7)->where('taken', true)->count() }} of 7">
    @foreach($last7 as $day)
        <div class="flex flex-col items-center gap-0.5" title="{{ $day['date']->format('D d M') }}: {{ $day['taken'] ? 'taken' : 'not logged' }}">
            <span class="block h-3.5 w-3.5 rounded-full border {{ $day['taken'] ? 'bg-emerald-500 border-emerald-600' : ($day['date']->isToday() ? 'bg-white border-slate-400 border-dashed' : 'bg-slate-200 border-slate-300') }}"></span>
            <span class="text-[9px] font-bold text-slate-400 leading-none">{{ substr($day['date']->format('D'), 0, 1) }}</span>
        </div>
    @endforeach
</div>
