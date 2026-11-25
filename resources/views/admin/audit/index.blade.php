<x-app-layout>
    <x-slot name="header">
        <div class="hidden"></div>
    </x-slot>

    @php
        $categoryStyle = [
            'change' => 'bg-teal-100 text-teal-700 border-teal-200',
            'view' => 'bg-slate-100 text-slate-700 border-slate-200',
            'auth' => 'bg-violet-100 text-violet-700 border-violet-200',
        ];
        $field = 'w-full rounded-xl border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500';
        $hasFilters = collect($filters)->filter()->isNotEmpty();
    @endphp

    <div class="py-8 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <a href="{{ route('admin.dashboard') }}" class="text-sm font-bold text-slate-500 hover:text-teal-700">&larr; Back to Admin Dashboard</a>
                    <h1 class="text-2xl font-extrabold text-slate-900 mt-2">Audit Log</h1>
                    <p class="text-sm text-slate-500 mt-1">Who viewed or changed patient records, and every sign-in attempt. Entries cannot be edited or deleted.</p>
                </div>
                <a href="{{ route('admin.audit.export', request()->query()) }}" class="px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-bold shadow-sm">
                    Export CSV{{ $hasFilters ? ' (filtered)' : '' }}
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Entries today</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2">{{ $stats['today'] }}</p>
                </div>
                <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Patient record views · 7 days</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2">{{ $stats['patientViews7d'] }}</p>
                </div>
                <a href="{{ route('admin.audit.index', ['q' => 'login_failed', 'from' => now()->subDays(6)->toDateString()]) }}"
                   class="bg-white border {{ $stats['failedLogins7d'] > 10 ? 'border-red-300' : 'border-slate-200' }} rounded-2xl p-5 shadow-sm hover:border-red-300">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Failed sign-ins · 7 days</p>
                    <p class="text-3xl font-extrabold {{ $stats['failedLogins7d'] > 10 ? 'text-red-600' : 'text-slate-900' }} mt-2">{{ $stats['failedLogins7d'] }}</p>
                </a>
            </div>

            <form method="GET" action="{{ route('admin.audit.index') }}" class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3">
                    <div class="lg:col-span-2">
                        <label for="q" class="block text-xs font-bold text-slate-600 mb-1">Search</label>
                        <input id="q" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Name, action, description, IP…" class="{{ $field }}">
                    </div>
                    <div>
                        <label for="category" class="block text-xs font-bold text-slate-600 mb-1">Type</label>
                        <select id="category" name="category" class="{{ $field }}">
                            <option value="">All</option>
                            @foreach($categories as $key => $label)
                                <option value="{{ $key }}" @selected($filters['category'] === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="user" class="block text-xs font-bold text-slate-600 mb-1">Staff member</label>
                        <select id="user" name="user" class="{{ $field }}">
                            <option value="">Anyone</option>
                            @foreach($staff as $member)
                                <option value="{{ $member->id }}" @selected($filters['user'] === $member->id)>{{ $member->name }} ({{ $member->role }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="patient" class="block text-xs font-bold text-slate-600 mb-1">Patient</label>
                        <select id="patient" name="patient" class="{{ $field }}">
                            <option value="">Any patient</option>
                            @foreach($patients as $p)
                                <option value="{{ $p->id }}" @selected($filters['patient'] === $p->id)>{{ $p->user->name ?? 'Patient #' . $p->id }}{{ $p->archived_at ? ' (archived)' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label for="from" class="block text-xs font-bold text-slate-600 mb-1">From</label>
                            <input id="from" type="date" name="from" value="{{ $filters['from'] }}" class="{{ $field }}">
                        </div>
                        <div>
                            <label for="to" class="block text-xs font-bold text-slate-600 mb-1">To</label>
                            <input id="to" type="date" name="to" value="{{ $filters['to'] }}" class="{{ $field }}">
                        </div>
                    </div>
                </div>
                <div class="mt-4 flex gap-2">
                    <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-sm font-bold">Apply filters</button>
                    @if($hasFilters)
                        <a href="{{ route('admin.audit.index') }}" class="px-4 py-2 rounded-xl border border-slate-300 bg-white text-slate-700 text-sm font-bold hover:bg-slate-50">Clear</a>
                    @endif
                </div>
            </form>

            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                            <tr>
                                <th class="py-3 px-4 whitespace-nowrap">When</th>
                                <th class="py-3 px-4">Who</th>
                                <th class="py-3 px-4">What</th>
                                <th class="py-3 px-4">Patient</th>
                                <th class="py-3 px-4">IP address</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($logs as $log)
                                @php $category = $log->category(); @endphp
                                <tr class="align-top {{ $log->action === 'auth.login_failed' || $log->action === 'auth.locked_out' ? 'bg-red-50/40' : '' }}">
                                    <td class="py-3 px-4 whitespace-nowrap text-slate-600">
                                        <span class="font-semibold text-slate-800">{{ $log->created_at->format('d M Y') }}</span><br>
                                        <span class="text-xs">{{ $log->created_at->format('H:i:s') }}</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="font-bold text-slate-800">{{ $log->actor_name }}</span><br>
                                        <span class="text-xs text-slate-500">{{ ucfirst($log->actor_role) }}</span>
                                    </td>
                                    <td class="py-3 px-4 max-w-xl">
                                        <span class="inline-block px-2 py-0.5 rounded-full border text-[11px] font-bold {{ $categoryStyle[$category] }}">{{ $log->actionLabel() }}</span>
                                        <p class="mt-1 text-slate-700">{{ $log->description }}</p>
                                        @if($log->changes)
                                            <details class="mt-1">
                                                <summary class="cursor-pointer text-xs font-bold text-teal-600 hover:text-teal-800">{{ count($log->changes) }} {{ \Illuminate\Support\Str::plural('field', count($log->changes)) }}</summary>
                                                <dl class="mt-2 space-y-1 rounded-lg bg-slate-50 border border-slate-200 p-2 text-xs">
                                                    @foreach($log->changes as $fieldName => $change)
                                                        <div class="grid grid-cols-[minmax(7rem,auto)_1fr] gap-2">
                                                            <dt class="font-bold text-slate-600">{{ str_replace('_', ' ', $fieldName) }}</dt>
                                                            <dd class="text-slate-700 break-words">
                                                                @if($change['from'] !== null)<span class="line-through text-red-600">{{ is_scalar($change['from']) ? $change['from'] : json_encode($change['from']) }}</span> &rarr; @endif
                                                                <span class="text-emerald-700 font-semibold">{{ $change['to'] === null ? '(empty)' : (is_scalar($change['to']) ? $change['to'] : json_encode($change['to'])) }}</span>
                                                            </dd>
                                                        </div>
                                                    @endforeach
                                                </dl>
                                            </details>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        @if($log->patient_id)
                                            <a href="{{ route('admin.audit.index', ['patient' => $log->patient_id]) }}" class="font-semibold text-teal-600 hover:text-teal-800" title="Show all entries for this patient">
                                                {{ $log->patient?->user?->name ?? 'Patient #' . $log->patient_id }}
                                            </a>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-xs text-slate-500 whitespace-nowrap" title="{{ $log->user_agent }}">{{ $log->ip_address ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-14 text-center text-slate-500">
                                        {{ $hasFilters ? 'No entries match these filters.' : 'No audit entries yet. They appear as soon as people sign in or open patient records.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($logs->hasPages())
                    <div class="px-4 py-3 border-t border-slate-100">{{ $logs->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
