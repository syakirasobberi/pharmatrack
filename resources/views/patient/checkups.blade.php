<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Checkups') }}
        </h2>
    </x-slot>

    <div class="py-10 min-h-screen bg-gray-50">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white border border-gray-100 rounded-3xl p-8 shadow-sm">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-wide text-indigo-600">Health Records</p>
                        <h1 class="text-3xl font-extrabold text-gray-900 mt-1">All Checkups</h1>
                        <p class="text-sm text-gray-500 mt-2">Review your recorded blood pressure, blood sugar, and cholesterol readings.</p>
                    </div>
                    <a href="{{ route('patient.dashboard') }}" class="inline-flex items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-bold text-gray-700 hover:bg-gray-50">
                        Back to Dashboard
                    </a>
                </div>
            </div>

            <div class="bg-white border border-gray-100 rounded-3xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-indigo-50 text-indigo-900 font-bold border-b border-indigo-100">
                            <tr>
                                <th class="py-4 px-6">Date</th>
                                <th class="py-4 px-6">Blood Pressure</th>
                                <th class="py-4 px-6">Blood Sugar</th>
                                <th class="py-4 px-6">Cholesterol</th>
                                <th class="py-4 px-6">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($patient->healthCheckups as $checkup)
                                @php
                                    $systolic = (float) preg_replace('/[^0-9.].*/', '', $checkup->blood_pressure);
                                    $hasAlert = $systolic > 130 || $checkup->blood_sugar >= 5.6 || $checkup->cholesterol >= 5.2;
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="py-4 px-6 font-bold text-gray-900">
                                        {{ \Carbon\Carbon::parse($checkup->checkup_date)->format('d M Y, h:i A') }}
                                    </td>
                                    <td class="py-4 px-6 {{ $systolic > 130 ? 'text-red-600 font-bold' : 'text-gray-700' }}">
                                        {{ $checkup->blood_pressure ?: 'N/A' }}
                                    </td>
                                    <td class="py-4 px-6 {{ $checkup->blood_sugar >= 5.6 ? 'text-red-600 font-bold' : 'text-gray-700' }}">
                                        {{ $checkup->blood_sugar }} mmol/L
                                    </td>
                                    <td class="py-4 px-6 {{ $checkup->cholesterol >= 5.2 ? 'text-orange-600 font-bold' : 'text-gray-700' }}">
                                        {{ $checkup->cholesterol }} mmol/L
                                    </td>
                                    <td class="py-4 px-6">
                                        @if($hasAlert)
                                            <span class="px-3 py-1 rounded-full bg-amber-100 text-amber-700 text-xs font-bold">Monitor</span>
                                        @else
                                            <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold">Normal</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center text-gray-500">
                                        No checkup records are available yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
