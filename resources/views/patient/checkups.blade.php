<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Checkups') }}
        </h2>
    </x-slot>

    <div class="py-10 min-h-screen bg-gray-50">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @php
                $latestCheckup = $patient->healthCheckups->first();
                $checkupAlertMessage = null;

                if (! $latestCheckup) {
                    $checkupAlertMessage = 'You have not had a health check-up yet. Visit the pharmacy counter and your pharmacist will do your first one.';
                } elseif (\Carbon\Carbon::parse($latestCheckup->checkup_date)->lt(\Carbon\Carbon::today()->subDays(90))) {
                    $lastCheckup = \Carbon\Carbon::parse($latestCheckup->checkup_date);
                    $checkupAlertMessage = "Your last check-up was {$lastCheckup->diffForHumans()} ({$lastCheckup->format('d M Y')}). We recommend one every 3 months — visit the pharmacy counter when you can.";
                }
            @endphp

            <div class="bg-white border border-gray-100 rounded-3xl p-8 shadow-sm">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wide text-teal-600">Health Records</p>
                        <h1 class="text-3xl font-extrabold text-gray-900 mt-1">My Check-ups</h1>
                        <p class="text-sm text-gray-500 mt-2">Your test results from each visit, newest first.</p>
                    </div>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <a href="{{ route('patient.summary.download') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-teal-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Download PDF
                        </a>
                        <form action="{{ route('patient.summary.email') }}" method="POST">
                            @csrf
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-teal-200 bg-teal-50 px-4 py-2.5 text-sm font-bold text-teal-700 hover:bg-teal-100" title="Send to {{ auth()->user()->email }}">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8m-18 8V6a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path></svg>
                                Send to Email
                            </button>
                        </form>
                        <a href="{{ route('patient.dashboard') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-bold text-gray-700 hover:bg-gray-50">
                            Back to Dashboard
                        </a>
                    </div>
                </div>
                <p class="mt-4 text-sm text-gray-500">Email option sends the PDF to <span class="font-semibold text-gray-700">{{ auth()->user()->email }}</span>.</p>
            </div>

            @if($checkupAlertMessage)
                <div class="bg-amber-50 border border-amber-200 rounded-3xl p-6 shadow-sm">
                    <div class="flex items-start gap-4">
                        <span class="inline-flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-700">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </span>
                        <div>
                            <h2 class="font-extrabold text-lg text-amber-900">Time for a check-up</h2>
                            <p class="text-sm text-amber-800 mt-1">{{ $checkupAlertMessage }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if($patient->healthCheckups->isNotEmpty())
                <p class="text-sm text-gray-500">
                    {{ $patient->healthCheckups->count() }} {{ \Illuminate\Support\Str::plural('check-up', $patient->healthCheckups->count()) }} on record.
                    Only tests that were done are shown. Tap an older check-up to open it.
                </p>
            @endif

            <div class="space-y-4">
                @forelse($patient->healthCheckups as $checkup)
                    @include('patient.partials.checkup-card', ['checkup' => $checkup, 'collapsible' => true, 'open' => $loop->first])
                @empty
                    <div class="bg-white border border-dashed border-gray-300 rounded-3xl p-12 text-center shadow-sm">
                        <h2 class="text-xl font-extrabold text-gray-800">No Checkup Records Yet</h2>
                        <p class="mt-2 text-sm text-gray-500">Please visit the pharmacy counter for your first health checkup.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
