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
                    $checkupAlertMessage = 'You have not completed any health checkup yet. Please visit the pharmacy counter for your first checkup.';
                } elseif (\Carbon\Carbon::parse($latestCheckup->checkup_date)->lt(\Carbon\Carbon::today()->subDays(90))) {
                    $lastCheckupDate = \Carbon\Carbon::parse($latestCheckup->checkup_date)->format('d M Y');
                    $checkupAlertMessage = "Your last health checkup was on {$lastCheckupDate}. Please schedule a follow-up checkup soon.";
                }
            @endphp

            <div class="bg-white border border-gray-100 rounded-3xl p-8 shadow-sm">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wide text-indigo-600">Health Records</p>
                        <h1 class="text-3xl font-extrabold text-gray-900 mt-1">All Checkups</h1>
                        <p class="text-sm text-gray-500 mt-2">Review every health checkup field recorded by your pharmacist.</p>
                    </div>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <a href="{{ route('patient.summary.download') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Download PDF
                        </a>
                        <form action="{{ route('patient.summary.email') }}" method="POST">
                            @csrf
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-bold text-indigo-700 hover:bg-indigo-100" title="Send to {{ auth()->user()->email }}">
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
                <div class="bg-red-50 border border-red-200 rounded-3xl p-6 shadow-sm">
                    <div class="flex items-start gap-4">
                        <span class="inline-flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl bg-red-100 text-red-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path>
                            </svg>
                        </span>
                        <div>
                            <h2 class="font-extrabold text-lg text-red-900">Checkup Reminder</h2>
                            <p class="text-sm text-red-800 mt-1">{{ $checkupAlertMessage }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="space-y-5">
                @forelse($patient->healthCheckups as $checkup)
                    @include('patient.partials.checkup-card', ['checkup' => $checkup])
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
