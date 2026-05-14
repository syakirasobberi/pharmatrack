<x-app-layout>
    <x-slot name="header">
        <div class="hidden"></div>
    </x-slot>

    <div class="py-8 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('admin.dashboard') }}" class="text-sm font-bold text-slate-500 hover:text-emerald-700">&larr; Back to Admin Dashboard</a>
                <h1 class="text-2xl font-extrabold text-slate-900 mt-2">Community Patients</h1>
                <p class="text-sm text-slate-500 mt-1">View patient coverage, pharmacist assignment, biometric readiness, and latest check-up status.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Total Patients</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2">{{ $totalPatients }}</p>
                </div>
                <div class="bg-white border border-emerald-200 rounded-2xl p-6 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-emerald-700">Biometrics Registered</p>
                    <p class="text-3xl font-extrabold text-emerald-700 mt-2">{{ $patientsWithBiometrics }}</p>
                </div>
                <div class="bg-white border border-amber-200 rounded-2xl p-6 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-amber-700">Unassigned Patients</p>
                    <p class="text-3xl font-extrabold text-amber-700 mt-2">{{ $unassignedPatients }}</p>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <h2 class="text-lg font-extrabold text-slate-800">Patient Directory</h2>
                    <form method="GET" action="{{ route('admin.patients.index') }}" class="flex gap-2">
                        <input type="search" name="search" value="{{ $search }}" placeholder="Search patient or pharmacist..." class="rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-200">
                        <button class="px-4 py-2 rounded-xl bg-slate-800 text-white text-sm font-bold">Search</button>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                            <tr>
                                <th class="py-4 px-6">Patient</th>
                                <th class="py-4 px-6">Assigned Pharmacist</th>
                                <th class="py-4 px-6">Profile</th>
                                <th class="py-4 px-6">Latest Check-up</th>
                                <th class="py-4 px-6">Biometric</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($patients as $patient)
                                @php
                                    $latestCheckup = $patient->healthCheckups->first();
                                @endphp
                                <tr class="hover:bg-slate-50">
                                    <td class="py-4 px-6">
                                        <p class="font-bold text-slate-800">{{ $patient->user->name }}</p>
                                        <p class="text-xs text-slate-500 mt-1">{{ $patient->user->email }}</p>
                                    </td>
                                    <td class="py-4 px-6 text-slate-600">{{ $patient->pharmacist?->name ?? 'Unassigned' }}</td>
                                    <td class="py-4 px-6 text-slate-600">
                                        {{ $patient->gender }}, {{ $patient->age }} years<br>
                                        <span class="text-xs">BMI {{ number_format($patient->bmi, 1) }}</span>
                                    </td>
                                    <td class="py-4 px-6 text-slate-600">
                                        @if($latestCheckup)
                                            <span class="font-bold text-slate-800">{{ \Carbon\Carbon::parse($latestCheckup->checkup_date)->format('d M Y') }}</span><br>
                                            <span class="text-xs">Sugar {{ $latestCheckup->blood_sugar ?? '-' }}, Cholesterol {{ $latestCheckup->cholesterol ?? '-' }}</span>
                                        @else
                                            <span class="text-amber-700 font-bold">No check-up recorded</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6">
                                        @if($patient->face_descriptor)
                                            <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold">Ready</span>
                                        @else
                                            <span class="px-3 py-1 rounded-full bg-amber-100 text-amber-700 text-xs font-bold">Missing</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-10 text-center text-slate-500">No patients found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-6 border-t border-slate-100">
                    {{ $patients->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
