<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Notifications') }}
        </h2>
    </x-slot>

    <div class="min-h-screen bg-gray-50 py-10">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-3xl bg-white p-8 shadow-sm border border-gray-100">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-indigo-600">Notification Inbox</p>
                        <h1 class="mt-1 text-3xl font-extrabold text-gray-900">Reminders and System Alerts</h1>
                        <p class="mt-2 max-w-2xl text-sm text-gray-500">
                            View pharmacy reminders and mark them as read after reviewing the details.
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-3 sm:min-w-64">
                        <div class="rounded-2xl border border-indigo-100 bg-indigo-50 px-4 py-3">
                            <p class="text-xs font-bold uppercase text-indigo-700">Unread</p>
                            <p class="mt-1 text-2xl font-black text-indigo-950">{{ $unreadCount }}</p>
                        </div>
                        <div class="rounded-2xl border border-gray-100 bg-gray-50 px-4 py-3">
                            <p class="text-xs font-bold uppercase text-gray-500">Total</p>
                            <p class="mt-1 text-2xl font-black text-gray-900">{{ $notifications->total() }}</p>
                        </div>
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="space-y-4">
                @forelse($notifications as $notification)
                    @php
                        $data = $notification->data;
                        $isUnread = is_null($notification->read_at);
                        $type = $data['type'] ?? class_basename($notification->type);
                        $actionUrl = $data['action_url'] ?? match ($type) {
                            'checkup_due' => route('patient.checkups'),
                            'medication_update_due' => route('patient.medications'),
                            default => null,
                        };
                        $metaDate = $data['last_checkup_date'] ?? $data['last_medication_update'] ?? null;
                    @endphp

                    <article class="rounded-3xl border {{ $isUnread ? 'border-indigo-200 bg-indigo-50/70' : 'border-gray-100 bg-white' }} p-6 shadow-sm">
                        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                            <div class="flex gap-4">
                                <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl {{ $isUnread ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-500' }}">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 00-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 01-6 0m6 0H9"></path>
                                    </svg>
                                </span>

                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="text-lg font-extrabold text-gray-900">
                                            {{ $data['title'] ?? 'Notification' }}
                                        </h2>
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-extrabold {{ $isUnread ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $isUnread ? 'Unread' : 'Read' }}
                                        </span>
                                    </div>

                                    <p class="mt-2 text-sm font-medium leading-6 text-gray-700">
                                        {{ $data['message'] ?? 'You have a new system notification.' }}
                                    </p>

                                    <div class="mt-4 flex flex-wrap gap-2 text-xs font-bold text-gray-500">
                                        <span class="rounded-full bg-white px-3 py-1 ring-1 ring-gray-200">
                                            Received {{ $notification->created_at->diffForHumans() }}
                                        </span>
                                        @if($metaDate)
                                            <span class="rounded-full bg-white px-3 py-1 ring-1 ring-gray-200">
                                                Related date: {{ \Carbon\Carbon::parse($metaDate)->format('d M Y') }}
                                            </span>
                                        @endif
                                        @if(! $isUnread && $notification->read_at)
                                            <span class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-700 ring-1 ring-emerald-100">
                                                Read {{ $notification->read_at->diffForHumans() }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row lg:flex-col">
                                @if($actionUrl)
                                    <a href="{{ $actionUrl }}" class="inline-flex items-center justify-center rounded-xl border border-indigo-200 bg-white px-4 py-2.5 text-sm font-extrabold text-indigo-700 hover:bg-indigo-50">
                                        Open Reminder
                                    </a>
                                @endif

                                @if($isUnread)
                                    <form action="{{ route('patient.notifications.read', $notification->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-extrabold text-white shadow-sm hover:bg-indigo-700">
                                            Mark as Read
                                        </button>
                                    </form>
                                @else
                                    <span class="inline-flex items-center justify-center rounded-xl bg-emerald-50 px-4 py-2.5 text-sm font-extrabold text-emerald-700 ring-1 ring-emerald-100">
                                        Already Read
                                    </span>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-3xl border-2 border-dashed border-gray-300 bg-white p-12 text-center shadow-sm">
                        <h2 class="text-xl font-extrabold text-gray-800">No Notifications Yet</h2>
                        <p class="mt-2 text-sm text-gray-500">New check-up and medication reminders will appear here.</p>
                    </div>
                @endforelse
            </div>

            @if($notifications->hasPages())
                <div class="rounded-2xl bg-white px-5 py-4 shadow-sm border border-gray-100">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
