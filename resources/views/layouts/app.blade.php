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
    </head>
    <body class="font-sans antialiased text-gray-900 bg-gray-100">
        
        <div class="flex h-screen overflow-hidden">
            
            @php
                $sidebarBg = 'bg-blue-900';
                $borderClass = 'border-blue-800';
                $brandColor = 'text-blue-300';
                
                if(Auth::user()->role == 'admin') {
                    $sidebarBg = 'bg-slate-900 border-r border-slate-800';
                    $borderClass = 'border-slate-800';
                    $brandColor = 'text-emerald-400';
                } elseif(Auth::user()->role == 'patient') {
                    $sidebarBg = 'bg-indigo-900 border-r border-indigo-800';
                    $borderClass = 'border-indigo-800';
                    $brandColor = 'text-indigo-400';
                }
            @endphp

            <aside class="w-64 text-white flex flex-col shadow-lg transition-colors duration-300 {{ $sidebarBg }}">
                
                <div class="h-16 flex items-center justify-center border-b {{ $borderClass }}">
                    <h1 class="text-2xl font-extrabold tracking-wider">Pharma<span class="{{ $brandColor }}">Track</span></h1>
                </div>

                <nav class="flex-1 px-4 py-6 space-y-3 overflow-y-auto">
                    
                    @if(Auth::user()->role == 'admin')
                        <a href="{{ route('admin.dashboard') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-slate-800 text-emerald-400 font-bold border-l-4 border-emerald-500' : 'text-slate-400 hover:bg-slate-800 hover:text-emerald-300' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                            Command Center
                        </a>

                        <a href="{{ route('admin.pharmacists.index') }}"
                           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors {{ request()->routeIs('admin.pharmacists.*') ? 'bg-slate-800 text-emerald-400 font-bold border-l-4 border-emerald-500' : 'text-slate-400 hover:bg-slate-800 hover:text-emerald-300 font-medium' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            Manage Staff
                        </a>

                        <a href="{{ route('admin.patients.index') }}"
                           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors {{ request()->routeIs('admin.patients.*') ? 'bg-slate-800 text-emerald-400 font-bold border-l-4 border-emerald-500' : 'text-slate-400 hover:bg-slate-800 hover:text-emerald-300 font-medium' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m6-6a4 4 0 11-8 0 4 4 0 018 0zm6 2a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            Patient Overview
                        </a>

                        <a href="{{ route('admin.reports.index') }}"
                           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors {{ request()->routeIs('admin.reports.*') ? 'bg-slate-800 text-emerald-400 font-bold border-l-4 border-emerald-500' : 'text-slate-400 hover:bg-slate-800 hover:text-emerald-300 font-medium' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m4 6V7m4 10v-3M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            Reports
                        </a>

                        <a href="{{ route('admin.settings.index') }}"
                           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors {{ request()->routeIs('admin.settings.*') ? 'bg-slate-800 text-emerald-400 font-bold border-l-4 border-emerald-500' : 'text-slate-400 hover:bg-slate-800 hover:text-emerald-300 font-medium' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            System Settings
                        </a>

                    @elseif(Auth::user()->role == 'patient')
                        {{-- My Health --}}
                        <a href="{{ route('patient.dashboard') }}"
                           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors font-medium"
                           style="background:{{ request()->routeIs('patient.dashboard') ? '#4338ca' : 'transparent' }};color:{{ request()->routeIs('patient.dashboard') ? '#fff' : '#c7d2fe' }};border-left:{{ request()->routeIs('patient.dashboard') ? '4px solid #a5b4fc' : '4px solid transparent' }};">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                            My Health
                        </a>

                        {{-- My Medications --}}
                        <a href="{{ route('patient.medications') }}"
                           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors font-medium"
                           style="background:{{ request()->routeIs('patient.medications') ? '#4338ca' : 'transparent' }};color:{{ request()->routeIs('patient.medications') ? '#fff' : '#c7d2fe' }};border-left:{{ request()->routeIs('patient.medications') ? '4px solid #a5b4fc' : '4px solid transparent' }};">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            My Medications
                        </a>

                        <a href="{{ route('patient.checkups') }}"
                           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors font-medium"
                           style="background:{{ request()->routeIs('patient.checkups') ? '#4338ca' : 'transparent' }};color:{{ request()->routeIs('patient.checkups') ? '#fff' : '#c7d2fe' }};border-left:{{ request()->routeIs('patient.checkups') ? '4px solid #a5b4fc' : '4px solid transparent' }};">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-3-3v6m8-3a8 8 0 11-16 0 8 8 0 0116 0z"></path></svg>
                            My Checkups
                        </a>

                        {{-- Download Summary --}}
                        <a href="{{ route('patient.summary.download') }}"
                           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors font-medium"
                           style="color:#c7d2fe;border-left:4px solid transparent;">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Download Summary
                        </a>

                    @else
                        <a href="{{ route('pharmacist.dashboard') }}" 
                           class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors {{ request()->routeIs('pharmacist.dashboard') ? 'bg-blue-700 font-bold border-l-4 border-blue-300' : 'hover:bg-blue-800 text-blue-100' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                            Dashboard
                        </a>

                        <a href="{{ route('pharmacist.patients.index') }}"
                           class="flex items-center gap-3 px-4 py-3 mt-2 rounded-lg transition-colors {{ request()->routeIs('pharmacist.patients.index', 'pharmacist.patients.show', 'pharmacist.patients.summary', 'pharmacist.patients.summary.download', 'pharmacist.patients.medical.*', 'pharmacist.medication.*', 'pharmacist.checkups.*') ? 'bg-blue-700 font-bold border-l-4 border-blue-300 text-white' : 'hover:bg-blue-800 text-blue-100' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            Patient List
                        </a>

                        <a href="{{ route('pharmacist.patients.create') }}" 
                           class="flex items-center gap-3 px-4 py-3 mt-2 rounded-lg transition-colors {{ request()->routeIs('pharmacist.patients.create') ? 'bg-blue-700 font-bold border-l-4 border-blue-300' : 'hover:bg-blue-800 text-blue-100' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                            Register Patient
                        </a>

                        
                    @endif

                </nav>

                <div class="p-4 border-t {{ $borderClass }} {{ Auth::user()->role == 'admin' ? 'bg-slate-950' : (Auth::user()->role == 'patient' ? 'bg-indigo-950' : 'bg-blue-950') }}">
                    <p class="text-sm font-semibold">{{ Auth::user()->name }}</p>
                    <p class="text-xs capitalize font-bold {{ $brandColor }}">{{ Auth::user()->role }}</p>
                </div>
            </aside>

            <div class="flex-1 flex flex-col overflow-hidden">
                
                <header class="h-16 bg-white shadow-sm flex items-center justify-between px-8 z-10 border-b border-gray-200">
                    
                    <div class="text-xl font-bold text-gray-800">
                        {{ $header ?? '' }}
                    </div>

                    <div class="flex items-center gap-6">
                        <a href="{{ route('profile.edit') }}" class="text-sm font-semibold text-gray-600 hover:text-blue-700 transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            My Profile
                        </a>
                        
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-sm px-4 py-2 bg-red-50 text-red-600 font-bold rounded-lg hover:bg-red-100 transition-colors">
                                Log Out
                            </button>
                        </form>
                    </div>
                </header>

                <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50/50">
                    {{ $slot }}
                </main>

            </div>

        </div>
    </body>
</html>
