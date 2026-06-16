<x-app-layout>
    <x-slot name="header">
        <div class="hidden"></div>
    </x-slot>

    <div class="py-8 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('admin.dashboard') }}" class="text-sm font-bold text-slate-500 hover:text-emerald-700">&larr; Back to Admin Dashboard</a>
                <h1 class="text-2xl font-extrabold text-slate-900 mt-2">Reports & Monitoring</h1>
                <p class="text-sm text-slate-500 mt-1">System-wide operational reporting for users, check-ups, clinical alerts, and medication follow-ups.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Users</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2">{{ $totalUsers }}</p>
                </div>
                <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Pharmacists</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2">{{ $totalPharmacists }}</p>
                </div>
                <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Patients</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2">{{ $totalPatients }}</p>
                </div>
                <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Check-ups</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2">{{ $totalCheckups }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                    <h2 class="text-lg font-extrabold text-slate-800 mb-4">Monthly Check-up Volume</h2>
                    <div class="space-y-3">
                        @forelse($monthlyCheckups as $month)
                            <div class="flex items-center justify-between border border-slate-100 rounded-xl p-3">
                                <span class="font-bold text-slate-700">{{ \Carbon\Carbon::create($month->year, $month->month)->format('M Y') }}</span>
                                <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-bold">{{ $month->total }} records</span>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No check-up records yet.</p>
                        @endforelse
                    </div>
                </div>

                <div class="lg:col-span-2 bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                    <h2 class="text-lg font-extrabold text-slate-800 mb-4">Clinical Alerts</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-50 text-slate-600 font-bold">
                                <tr>
                                    <th class="py-3 px-4">Patient</th>
                                    <th class="py-3 px-4">Date</th>
                                    <th class="py-3 px-4">Blood Sugar</th>
                                    <th class="py-3 px-4">Cholesterol</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($highRiskCheckups as $checkup)
                                    <tr>
                                        <td class="py-3 px-4 font-bold text-slate-800">{{ $checkup->patient->user->name ?? 'Unknown' }}</td>
                                        <td class="py-3 px-4 text-slate-600">{{ \Carbon\Carbon::parse($checkup->checkup_date)->format('d M Y') }}</td>
                                        <td class="py-3 px-4 {{ is_numeric($checkup->blood_sugar) && ($checkup->blood_sugar < 3.9 || $checkup->blood_sugar > 6.0) ? 'text-red-700 font-bold' : 'text-slate-600' }}">{{ $checkup->blood_sugar ?? '-' }}</td>
                                        <td class="py-3 px-4 {{ $checkup->cholesterol >= 5.2 ? 'text-amber-700 font-bold' : 'text-slate-600' }}">{{ $checkup->cholesterol ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="py-8 text-center text-slate-500">No clinical alerts detected.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                <h2 class="text-lg font-extrabold text-slate-800 mb-4">Medication Follow-ups</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @forelse($medicationFollowUps as $medication)
                        @php
                            $lastTaken = $medication->last_taken ? \Carbon\Carbon::parse($medication->last_taken) : null;
                            $endDate = $medication->end_date ? \Carbon\Carbon::parse($medication->end_date) : null;
                        @endphp
                        <div class="border border-amber-100 bg-amber-50/50 rounded-2xl p-4">
                            <p class="font-bold text-slate-900">{{ $medication->patient->user->name ?? 'Unknown' }}</p>
                            <p class="text-sm text-slate-700 mt-1">{{ $medication->name }}</p>
                            <p class="text-xs text-amber-700 mt-2">
                                @if($lastTaken && $lastTaken->lt(today()->subDays(7)))
                                    Last dose was {{ $lastTaken->diffInDays(today()) }} days ago.
                                @elseif(! $lastTaken)
                                    Latest dose date is missing.
                                @elseif($endDate)
                                    Treatment ends on {{ $endDate->format('d M Y') }}.
                                @endif
                            </p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No medication follow-ups need attention.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
