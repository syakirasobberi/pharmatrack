<x-app-layout>
    <x-slot name="header">
        <div class="hidden"></div>
    </x-slot>

    <div class="py-8 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div>
                <a href="{{ route('admin.dashboard') }}" class="text-sm font-bold text-slate-500 hover:text-emerald-700">&larr; Back to Admin Dashboard</a>
                <h1 class="text-2xl font-extrabold text-slate-900 mt-2">System Settings</h1>
                <p class="text-sm text-slate-500 mt-1">System configuration and access-control overview for the current deployment.</p>
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

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                    <h2 class="text-lg font-extrabold text-slate-800 mb-4">Application Name</h2>
                    <form method="POST" action="{{ route('admin.settings.updateAppName') }}" class="space-y-4">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label for="app_name" class="block text-sm font-bold text-slate-700 mb-1.5">App name</label>
                            <input
                                id="app_name"
                                type="text"
                                name="app_name"
                                value="{{ old('app_name', $settings['app_name']) }}"
                                required
                                maxlength="60"
                                class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-500 focus:ring-emerald-200"
                            >
                            <p class="text-xs text-slate-500 mt-2">This updates the Laravel `APP_NAME` value used by the page title and system branding.</p>
                        </div>

                        <button type="submit" class="px-4 py-2.5 rounded-xl bg-emerald-600 text-white text-sm font-bold hover:bg-emerald-700">
                            Save App Name
                        </button>
                    </form>
                </div>

                <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                    <h2 class="text-lg font-extrabold text-slate-800 mb-4">Application Details</h2>
                    <dl class="space-y-4">
                        @foreach($settings as $label => $value)
                            <div class="flex items-center justify-between border-b border-slate-100 pb-3 last:border-0">
                                <dt class="text-sm font-bold text-slate-500">{{ str_replace('_', ' ', ucfirst($label)) }}</dt>
                                <dd class="text-sm font-bold text-slate-900">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

                <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                    <h2 class="text-lg font-extrabold text-slate-800 mb-4">Role Distribution</h2>
                    <div class="space-y-3">
                        @forelse($roleCounts as $role => $total)
                            <div class="flex items-center justify-between rounded-xl border border-slate-100 p-4">
                                <span class="font-bold text-slate-700 capitalize">{{ $role ?: 'Unset' }}</span>
                                <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-bold">{{ $total }} users</span>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No users found.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                <h2 class="text-lg font-extrabold text-slate-800 mb-4">Admin Shortcuts</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <a href="{{ route('admin.pharmacists.index') }}" class="rounded-xl border border-slate-200 p-4 font-bold text-slate-700 hover:bg-slate-50">Manage Pharmacists</a>
                    <a href="{{ route('admin.patients.index') }}" class="rounded-xl border border-slate-200 p-4 font-bold text-slate-700 hover:bg-slate-50">View Patients</a>
                    <a href="{{ route('admin.reports.index') }}" class="rounded-xl border border-slate-200 p-4 font-bold text-slate-700 hover:bg-slate-50">Open Reports</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
