<x-app-layout>
    <x-slot name="header">
        <div class="hidden"></div>
    </x-slot>

    <div class="py-8 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="bg-slate-800 rounded-2xl p-8 shadow-lg text-white flex flex-col md:flex-row justify-between items-start md:items-center gap-6 relative overflow-hidden">
                <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-emerald-500 opacity-20 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 left-20 w-32 h-32 bg-slate-600 opacity-40 rounded-full blur-xl"></div>
                
                <div class="relative z-10">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="bg-emerald-500 text-white text-xs font-bold px-2.5 py-1 rounded-md uppercase tracking-wider">System Administrator</span>
                    </div>
                    <h1 class="text-3xl font-extrabold mb-1">PharmaTrack Command Center</h1>
                    <p class="text-slate-300 text-sm">Monitor system health, manage staff access, and oversee clinical operations.</p>
                </div>
                <div class="relative z-10">
                    <button class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold rounded-xl shadow-md transition-colors flex items-center gap-2">
                        ⚙️ System Settings
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 flex items-center gap-4 hover:shadow-md transition-shadow cursor-pointer">
                    <div class="w-14 h-14 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center text-2xl border border-indigo-100">
                        👨‍⚕️
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 font-bold">Registered Pharmacists</p>
                        <h3 class="text-2xl font-extrabold text-slate-800">{{ $totalPharmacists ?? 0 }}</h3>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 flex items-center gap-4 hover:shadow-md transition-shadow cursor-pointer">
                    <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center text-2xl border border-emerald-100">
                        👥
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 font-bold">Total Community Patients</p>
                        <h3 class="text-2xl font-extrabold text-slate-800">{{ $totalPatients ?? 0 }}</h3>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 flex items-center gap-4 hover:shadow-md transition-shadow cursor-pointer">
                    <div class="w-14 h-14 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center text-2xl border border-amber-100">
                        📊
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 font-bold">Health Records Logged</p>
                        <h3 class="text-2xl font-extrabold text-slate-800">{{ $totalCheckups ?? 0 }}</h3>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-extrabold text-lg text-slate-800 flex items-center gap-2">
                        🛡️ Recent Pharmacist Staff
                    </h3>
                    <a href="#" class="text-sm text-emerald-600 hover:text-emerald-800 font-bold">Manage All Staff &rarr;</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-600 font-bold border-y border-slate-200">
                            <tr>
                                <th class="py-4 px-6">Staff Name</th>
                                <th class="py-4 px-6">Email Address</th>
                                <th class="py-4 px-6">Joined Date</th>
                                <th class="py-4 px-6">Account Status</th>
                                <th class="py-4 px-6 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($recentPharmacists as $staff)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="py-4 px-6 font-bold text-slate-800 flex items-center gap-3">
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($staff->name) }}&background=f1f5f9&color=0f172a" class="w-9 h-9 rounded-full border border-slate-200">
                                    {{ $staff->name }}
                                </td>
                                <td class="py-4 px-6 text-slate-600">{{ $staff->email }}</td>
                                <td class="py-4 px-6 text-slate-600">{{ $staff->created_at->format('d M Y') }}</td>
                                <td class="py-4 px-6">
                                    <span class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold">Active</span>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <button class="px-3 py-1.5 border border-slate-300 text-slate-600 hover:bg-slate-100 font-bold rounded-lg transition-colors text-xs">
                                        Review Access
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-500 italic">No pharmacist staff registered yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>