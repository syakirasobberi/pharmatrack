{{-- Last 7 days of logged doses. $last7 from App\Support\Adherence::forMedication() --}}
<div class="flex items-end gap-1" aria-label="{{ __('Doses logged in the last 7 days: :count of 7', ['count' => collect($last7)->where('taken', true)->count()]) }}">
    @foreach($last7 as $day)
        <div class="flex flex-col items-center gap-0.5" title="{{ $day['date']->translatedFormat('D d M') }}: {{ $day['taken'] ? __('taken') : __('not logged') }}">
            <span class="block h-3.5 w-3.5 rounded-full border {{ $day['taken'] ? 'bg-emerald-500 border-emerald-600' : ($day['date']->isToday() ? 'bg-white border-slate-400 border-dashed' : 'bg-slate-200 border-slate-300') }}"></span>
            <span class="text-[9px] font-bold text-slate-400 leading-none">{{ mb_substr($day['date']->translatedFormat('D'), 0, 1) }}</span>
        </div>
    @endforeach
</div>
