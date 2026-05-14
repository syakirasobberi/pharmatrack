<x-app-layout>
    <x-slot name="header">
        <div class="hidden"></div>
    </x-slot>

    <div class="py-8 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <a href="{{ route('admin.dashboard') }}" class="text-sm font-bold text-slate-500 hover:text-emerald-700">&larr; Back to Admin Dashboard</a>
                    <h1 class="text-2xl font-extrabold text-slate-900 mt-2">Pharmacist Staff</h1>
                    <p class="text-sm text-slate-500 mt-1">Manage pharmacist access and monitor assigned patient workload.</p>
                </div>
                <a href="{{ route('admin.settings.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-700 font-bold text-sm hover:bg-slate-100">System Settings</a>
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

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Total Pharmacists</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2">{{ $totalPharmacists }}</p>
                </div>
                <div class="bg-white border border-amber-200 rounded-2xl p-6 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-amber-700">Password Prompts Active</p>
                    <p class="text-3xl font-extrabold text-amber-700 mt-2">{{ $passwordPrompts }}</p>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                <form method="POST" action="{{ route('admin.pharmacists.store') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    @csrf
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="Staff name" required class="rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-200">
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="Email address" required class="rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-200">
                    <input type="password" name="password" placeholder="Temporary password" required class="rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-200">
                    <button type="submit" class="rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm px-4 py-2.5">Add Pharmacist</button>
                </form>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <h2 class="text-lg font-extrabold text-slate-800">All Pharmacist Accounts</h2>
                    <form method="GET" action="{{ route('admin.pharmacists.index') }}" class="flex gap-2">
                        <input type="search" name="search" value="{{ $search }}" placeholder="Search staff..." class="rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-200">
                        <button class="px-4 py-2 rounded-xl bg-slate-800 text-white text-sm font-bold">Search</button>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                            <tr>
                                <th class="py-4 px-6">Staff</th>
                                <th class="py-4 px-6">Assigned Patients</th>
                                <th class="py-4 px-6">Joined</th>
                                <th class="py-4 px-6">Status</th>
                                <th class="py-4 px-6 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($pharmacists as $staff)
                                <tr class="hover:bg-slate-50">
                                    <td class="py-4 px-6">
                                        <p class="font-bold text-slate-800">{{ $staff->name }}</p>
                                        <p class="text-xs text-slate-500 mt-1">{{ $staff->email }}</p>
                                    </td>
                                    <td class="py-4 px-6 font-bold text-slate-700">{{ $staff->assigned_patients_count }}</td>
                                    <td class="py-4 px-6 text-slate-600">{{ $staff->created_at->format('d M Y') }}</td>
                                    <td class="py-4 px-6">
                                        @if($staff->requires_password_change)
                                            <span class="px-3 py-1 rounded-full bg-amber-100 text-amber-700 text-xs font-bold">Password Change Required</span>
                                        @else
                                            <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold">Active</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <form method="POST" action="{{ route('admin.pharmacists.requirePasswordChange', $staff) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button class="px-3 py-1.5 border border-slate-300 text-slate-600 hover:bg-slate-100 font-bold rounded-lg text-xs">Require Password Change</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-10 text-center text-slate-500">No pharmacist accounts found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-6 border-t border-slate-100">
                    {{ $pharmacists->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
