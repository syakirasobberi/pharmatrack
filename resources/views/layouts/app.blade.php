<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'PharmaTrack') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    </head>
    <body class="font-sans antialiased text-gray-900 bg-gray-100">
        
        <div x-data="{ sidebarOpen: false }" class="flex min-h-dvh bg-gray-50 lg:h-dvh lg:overflow-hidden">
            
            @php
                // One sidebar theme for every role (admin, pharmacist, patient), matching the PharmaTrack logo.
                $sidebarBg = 'bg-teal-900 border-r border-teal-800';
                $borderClass = 'border-teal-800';
                $brandColor = 'text-teal-300';
                $patientContact = null;
                $patientUnreadNotificationsCount = 0;

                if(Auth::user()->role == 'patient') {
                    $patientUnreadNotificationsCount = Auth::user()->unreadNotifications()->count();
                    $patientContact = \App\Models\Patient::with('pharmacist')
                        ->where('user_id', Auth::id())
                        ->first();
                }

                $assignedPharmacist = $patientContact?->pharmacist;
                $pharmacistPhone = $assignedPharmacist?->phone_number;
                $whatsappNumber = $pharmacistPhone ? preg_replace('/\D+/', '', $pharmacistPhone) : null;

                if($whatsappNumber && str_starts_with($whatsappNumber, '0')) {
                    $whatsappNumber = '60' . substr($whatsappNumber, 1);
                }

                $whatsappLink = $whatsappNumber ? 'https://wa.me/' . $whatsappNumber : null;
            @endphp

            <div
                x-cloak
                x-show="sidebarOpen"
                x-transition.opacity
                @click="sidebarOpen = false"
                class="fixed inset-0 z-30 bg-gray-950/50 backdrop-blur-sm lg:hidden"
                aria-hidden="true"
            ></div>

            <aside
                class="fixed inset-y-0 left-0 z-40 w-72 max-w-[85vw] -translate-x-full text-white flex flex-col shadow-2xl transition duration-300 ease-out lg:static lg:z-auto lg:w-64 lg:max-w-none lg:translate-x-0 lg:shadow-lg {{ $sidebarBg }}"
                :class="{ 'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen }"
            >
                
                <div class="h-16 flex items-center justify-between gap-3 border-b px-4 {{ $borderClass }}">
                    <a href="{{ route('dashboard') }}" aria-label="PharmaTrack home"><x-brand theme="dark" :size="34" /></a>
                    <button
                        type="button"
                        @click="sidebarOpen = false"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-white/80 hover:bg-white/10 hover:text-white lg:hidden"
                        aria-label="Close navigation"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <nav class="flex-1 px-4 py-6 space-y-3 overflow-y-auto">
                    
                    @if(Auth::user()->role == 'admin')
                        <a href="{{ route('admin.dashboard') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-teal-700 font-bold border-l-4 border-teal-300' : 'hover:bg-teal-800 text-teal-100' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                            Admin Dashboard
                        </a>

                        <a href="{{ route('admin.pharmacists.index') }}"
                           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors {{ request()->routeIs('admin.pharmacists.*') ? 'bg-teal-700 font-bold border-l-4 border-teal-300' : 'hover:bg-teal-800 text-teal-100 font-medium' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            Manage Staff
                        </a>

                        <a href="{{ route('admin.patients.index') }}"
                           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors {{ request()->routeIs('admin.patients.*') ? 'bg-teal-700 font-bold border-l-4 border-teal-300' : 'hover:bg-teal-800 text-teal-100 font-medium' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m6-6a4 4 0 11-8 0 4 4 0 018 0zm6 2a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            Patient Overview
                        </a>

                        <a href="{{ route('admin.reports.index') }}"
                           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors {{ request()->routeIs('admin.reports.*') ? 'bg-teal-700 font-bold border-l-4 border-teal-300' : 'hover:bg-teal-800 text-teal-100 font-medium' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m4 6V7m4 10v-3M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            Reports
                        </a>

                        <a href="{{ route('admin.audit.index') }}"
                           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors {{ request()->routeIs('admin.audit.*') ? 'bg-teal-700 font-bold border-l-4 border-teal-300' : 'hover:bg-teal-800 text-teal-100 font-medium' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            Audit Log
                        </a>

                        <a href="{{ route('admin.settings.index') }}"
                           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors {{ request()->routeIs('admin.settings.*') ? 'bg-teal-700 font-bold border-l-4 border-teal-300' : 'hover:bg-teal-800 text-teal-100 font-medium' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            System Settings
                        </a>

                    @elseif(Auth::user()->role == 'patient')
                        {{-- My Health --}}
                        <a href="{{ route('patient.dashboard') }}"
                           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors font-medium"
                           style="background:{{ request()->routeIs('patient.dashboard') ? '#0f766e' : 'transparent' }};color:{{ request()->routeIs('patient.dashboard') ? '#fff' : '#ccfbf1' }};border-left:{{ request()->routeIs('patient.dashboard') ? '4px solid #5eead4' : '4px solid transparent' }};">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                            My Health
                        </a>

                        {{-- My Medications --}}
                        <a href="{{ route('patient.medications') }}"
                           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors font-medium"
                           style="background:{{ request()->routeIs('patient.medications') ? '#0f766e' : 'transparent' }};color:{{ request()->routeIs('patient.medications') ? '#fff' : '#ccfbf1' }};border-left:{{ request()->routeIs('patient.medications') ? '4px solid #5eead4' : '4px solid transparent' }};">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            My Medications
                        </a>

                        <a href="{{ route('patient.checkups') }}"
                           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors font-medium"
                           style="background:{{ request()->routeIs('patient.checkups') ? '#0f766e' : 'transparent' }};color:{{ request()->routeIs('patient.checkups') ? '#fff' : '#ccfbf1' }};border-left:{{ request()->routeIs('patient.checkups') ? '4px solid #5eead4' : '4px solid transparent' }};">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-3-3v6m8-3a8 8 0 11-16 0 8 8 0 0116 0z"></path></svg>
                            My Checkups
                        </a>

                        <a href="{{ route('patient.notifications.index') }}"
                           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors font-medium"
                           style="background:{{ request()->routeIs('patient.notifications.*') ? '#0f766e' : 'transparent' }};color:{{ request()->routeIs('patient.notifications.*') ? '#fff' : '#ccfbf1' }};border-left:{{ request()->routeIs('patient.notifications.*') ? '4px solid #5eead4' : '4px solid transparent' }};">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 00-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 01-6 0m6 0H9"></path></svg>
                            <span class="min-w-0 flex-1">Notifications</span>
                            @if($patientUnreadNotificationsCount > 0)
                                <span class="inline-flex min-w-6 items-center justify-center rounded-full bg-red-500 px-2 py-0.5 text-xs font-extrabold text-white">
                                    {{ $patientUnreadNotificationsCount }}
                                </span>
                            @endif
                        </a>

                        {{-- Health Summary PDF Options --}}
                        <div class="rounded-xl border border-teal-700/70 bg-teal-950/30 p-2">
                            <p class="px-2 pb-2 text-xs font-bold uppercase tracking-wider text-teal-300">Health Summary PDF</p>
                            <a href="{{ route('patient.summary.download') }}"
                               class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium text-teal-100 transition-colors hover:bg-teal-800">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                Download PDF
                            </a>
                            <form action="{{ route('patient.summary.email') }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left font-medium text-teal-100 transition-colors hover:bg-teal-800"
                                        title="Send to {{ Auth::user()->email }}">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8m-18 8V6a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path></svg>
                                    Send to Email
                                </button>
                            </form>
                        </div>

                    @else
                        <a href="{{ route('pharmacist.dashboard') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors {{ request()->routeIs('pharmacist.dashboard') ? 'bg-teal-700 font-bold border-l-4 border-teal-300' : 'hover:bg-teal-800 text-teal-100' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                            Dashboard
                        </a>

                        <a href="{{ route('pharmacist.patients.index') }}"
                           class="flex items-center gap-3 px-4 py-3 mt-2 rounded-lg transition-colors {{ request()->routeIs('pharmacist.patients.index', 'pharmacist.patients.show', 'pharmacist.patients.summary', 'pharmacist.patients.summary.download', 'pharmacist.patients.medical.*', 'pharmacist.medication.*', 'pharmacist.checkups.*') ? 'bg-teal-700 font-bold border-l-4 border-teal-300 text-white' : 'hover:bg-teal-800 text-teal-100' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            Patient List
                        </a>

                        <a href="{{ route('pharmacist.patients.create') }}" 
                           class="flex items-center gap-3 px-4 py-3 mt-2 rounded-lg transition-colors {{ request()->routeIs('pharmacist.patients.create') ? 'bg-teal-700 font-bold border-l-4 border-teal-300' : 'hover:bg-teal-800 text-teal-100' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                            Register Patient
                        </a>

                        <a href="{{ route('pharmacist.quickScan') }}"
                           class="flex items-center gap-3 px-4 py-3 mt-2 rounded-lg transition-colors {{ request()->routeIs('pharmacist.quickScan') ? 'bg-teal-700 font-bold border-l-4 border-teal-300' : 'hover:bg-teal-800 text-teal-100' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V6a2 2 0 012-2h2M4 16v2a2 2 0 002 2h2m8-16h2a2 2 0 012 2v2m-4 12h2a2 2 0 002-2v-2M9 10h.01M15 10h.01M9.5 15a3.5 3.5 0 005 0"/></svg>
                            Quick Scan
                        </a>
                    @endif

                </nav>

                <div class="p-4 border-t {{ $borderClass }} bg-teal-950">
                    <p class="text-sm font-semibold">{{ Auth::user()->name }}</p>
                    <p class="text-xs capitalize font-bold {{ $brandColor }}">{{ Auth::user()->role }}</p>
                </div>
            </aside>

            <div class="flex min-w-0 flex-1 flex-col lg:overflow-hidden">
                
                <header class="sticky top-0 z-20 min-h-16 bg-white shadow-sm flex flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:px-8 lg:py-0 border-b border-gray-200">
                    
                    <div class="flex min-w-0 items-center gap-3">
                        <button
                            type="button"
                            @click="sidebarOpen = true"
                            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-700 shadow-sm hover:bg-gray-50 lg:hidden"
                            aria-label="Open navigation"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>
                        <div class="min-w-0 text-lg font-bold text-gray-800 sm:text-xl">
                            {{ $header ?? '' }}
                        </div>
                    </div>

                    <div class="flex items-center gap-2 sm:gap-4">
                        <a href="{{ route('profile.edit') }}" class="text-sm font-semibold text-gray-600 hover:text-teal-700 transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            <span class="hidden sm:inline">My Profile</span>
                        </a>
                        
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-sm px-3 py-2 sm:px-4 bg-red-50 text-red-600 font-bold rounded-lg hover:bg-red-100 transition-colors">
                                Log Out
                            </button>
                        </form>
                    </div>
                </header>

                {{-- Patients get extra bottom space so the floating "Contact Us" button never covers content.
                     relative: absolutely-positioned bits inside tables (sr-only labels) stay inside this scroll area
                     instead of stretching the whole window (second scrollbar, page sliding up). --}}
                <main class="relative min-w-0 flex-1 overflow-x-hidden overflow-y-auto bg-gray-50/50 {{ Auth::user()->role == 'patient' ? 'pb-24' : '' }}">
                    @if(session('summary_success'))
                        <div class="mx-auto mt-5 max-w-5xl px-4 sm:px-6 lg:px-8">
                            <div class="rounded-2xl border border-green-200 bg-green-50 px-5 py-4 font-semibold text-green-800 shadow-sm">
                                {{ session('summary_success') }}
                            </div>
                        </div>
                    @endif

                    @if(session('summary_error'))
                        <div class="mx-auto mt-5 max-w-5xl px-4 sm:px-6 lg:px-8">
                            <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 font-semibold text-red-800 shadow-sm">
                                {{ session('summary_error') }}
                            </div>
                        </div>
                    @endif

                    {{ $slot }}
                    <footer class="text-center text-xs text-gray-400 font-medium py-4 border-t border-gray-100 mt-auto">
                        &copy; {{ date('Y') }} PharmaTrack &mdash; Smart Pharmacy System.
                    </footer>
                </main>

            </div>

            @if(Auth::user()->role == 'patient')
                {{-- pointer-events-none: the closed widget is a wide invisible box; only the pill and the open panel take clicks. z-40 keeps dialogs (z-50) on top. --}}
                <details id="contact-us" class="pointer-events-none fixed bottom-5 right-5 z-40 w-[calc(100vw-2.5rem)] max-w-sm group">
                    <summary class="pointer-events-auto ml-auto flex w-fit cursor-pointer list-none items-center gap-2 rounded-full bg-emerald-600 px-5 py-3 text-sm font-extrabold text-white shadow-lg shadow-emerald-700/20 transition hover:-translate-y-0.5 hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-200">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 11.5a8.4 8.4 0 01-8.7 8.4 9 9 0 01-3.8-.8L3 21l1.8-5.2a8.2 8.2 0 01-1-4A8.4 8.4 0 0112.3 3 8.4 8.4 0 0121 11.5z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.5 10.1c.5 2 2 3.6 4 4.4l1.2-1.2c.2-.2.5-.3.8-.2 1 .3 1.7.4 2.3.4"></path>
                        </svg>
                        Contact Us
                    </summary>

                    <div class="pointer-events-auto mt-3 rounded-2xl border border-emerald-100 bg-white p-5 text-sm shadow-2xl">
                        <p class="text-xs font-extrabold uppercase tracking-wide text-emerald-700">Need help?</p>
                        <h3 class="mt-1 text-lg font-extrabold text-gray-900">Contact Pharmacy Support</h3>
                        <p class="mt-2 text-gray-600">For urgent health questions, please visit the pharmacy counter.</p>

                        <div class="mt-4 rounded-xl bg-emerald-50 p-4">
                            <p class="text-xs font-bold uppercase text-emerald-700">Assigned Pharmacist</p>
                            <p class="mt-1 font-extrabold text-gray-900">{{ $assignedPharmacist?->name ?? 'Pharmacy Counter' }}</p>

                            @if($whatsappLink)
                                <a href="{{ $whatsappLink }}" target="_blank" rel="noopener" class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-extrabold text-white hover:bg-emerald-700">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 11.5a8.4 8.4 0 01-8.7 8.4 9 9 0 01-3.8-.8L3 21l1.8-5.2a8.2 8.2 0 01-1-4A8.4 8.4 0 0112.3 3 8.4 8.4 0 0121 11.5z"></path>
                                    </svg>
                                    WhatsApp Pharmacist
                                </a>
                                <p class="mt-2 text-xs font-semibold text-emerald-700">{{ $pharmacistPhone }}</p>
                            @else
                                <p class="mt-2 text-sm font-semibold text-gray-600">WhatsApp is not available yet because the pharmacist has not added a phone number.</p>
                            @endif

                            @if($assignedPharmacist?->email)
                                <a href="mailto:{{ $assignedPharmacist->email }}" class="mt-3 inline-flex items-center text-sm font-bold text-emerald-700 hover:text-emerald-900">
                                    {{ $assignedPharmacist->email }}
                                </a>
                            @endif
                        </div>
                    </div>
                </details>
            @endif

        </div>
    </body>
</html>
