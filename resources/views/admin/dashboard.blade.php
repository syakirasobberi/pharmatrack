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
                    <a href="{{ route('admin.settings.index') }}" class="px-5 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold rounded-xl shadow-md transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        System Settings
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <a href="{{ route('admin.pharmacists.index') }}" class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 flex items-center gap-4 hover:shadow-md transition-shadow cursor-pointer">
                    <div class="w-14 h-14 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center border border-indigo-100">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m6-6a4 4 0 11-8 0 4 4 0 018 0zm6 2a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 font-bold">Registered Pharmacists</p>
                        <h3 class="text-2xl font-extrabold text-slate-800">{{ $totalPharmacists ?? 0 }}</h3>
                    </div>
                </a>

                <a href="{{ route('admin.patients.index') }}" class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 flex items-center gap-4 hover:shadow-md transition-shadow cursor-pointer">
                    <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center border border-emerald-100">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 font-bold">Total Community Patients</p>
                        <h3 class="text-2xl font-extrabold text-slate-800">{{ $totalPatients ?? 0 }}</h3>
                    </div>
                </a>

                <a href="{{ route('admin.reports.index') }}" class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 flex items-center gap-4 hover:shadow-md transition-shadow cursor-pointer">
                    <div class="w-14 h-14 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center border border-amber-100">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17v-6m4 6V7m4 10v-3M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 font-bold">Health Records Logged</p>
                        <h3 class="text-2xl font-extrabold text-slate-800">{{ $totalCheckups ?? 0 }}</h3>
                    </div>
                </a>
            </div>

            @if(session('success'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-2xl font-bold">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-2xl">
                    <p class="font-bold mb-2">Please fix these items:</p>
                    <ul class="list-disc ml-5 text-sm space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
                    <div class="max-w-md">
                        <h3 class="font-extrabold text-lg text-slate-800">Create Pharmacist Account</h3>
                        <p class="text-sm text-slate-500 mt-2">Admins can create staff accounts and force first-login password changes for access control.</p>
                    </div>

                    <form method="POST" action="{{ route('admin.pharmacists.store') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 flex-1">
                        @csrf
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="Staff name" required class="rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-200">
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="Email address" required class="rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-200">
                        <input type="tel" name="phone_number" value="{{ old('phone_number') }}" placeholder="WhatsApp phone" required class="rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-200">
                        <input type="password" name="password" placeholder="Temporary password" required class="rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-200">
                        <button type="submit" class="rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm px-4 py-2.5">
                            Add Staff
                        </button>
                    </form>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="font-extrabold text-lg text-slate-800 flex items-center gap-2">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-slate-600 border border-slate-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </span>
                        Recent Pharmacist Staff
                    </h3>
                    <a href="{{ route('admin.pharmacists.index') }}" class="text-sm text-emerald-600 hover:text-emerald-800 font-bold">Manage All Staff &rarr;</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-600 font-bold border-y border-slate-200">
                            <tr>
                                <th class="py-4 px-6">Staff Name</th>
                                <th class="py-4 px-6">Email Address</th>
                                <th class="py-4 px-6">WhatsApp Phone</th>
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
                                <td class="py-4 px-6 text-slate-600">
                                    {{ $staff->phone_number ?: 'Needs update' }}
                                </td>
                                <td class="py-4 px-6 text-slate-600">{{ $staff->created_at->format('d M Y') }}</td>
                                <td class="py-4 px-6">
                                    @if($staff->requires_password_change)
                                        <span class="px-3 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-bold">Password Change Required</span>
                                    @else
                                        <span class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold">Active</span>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <form method="POST" action="{{ route('admin.pharmacists.requirePasswordChange', $staff) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button class="px-3 py-1.5 border border-slate-300 text-slate-600 hover:bg-slate-100 font-bold rounded-lg transition-colors text-xs">
                                            Require Password Change
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-500 italic">No pharmacist staff registered yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
