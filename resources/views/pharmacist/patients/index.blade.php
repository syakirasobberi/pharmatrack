<x-app-layout>
    <x-slot name="header">
        <div class="hidden"></div>
    </x-slot>

    <div class="py-8 bg-slate-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- PAGE HEADER -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white p-6 rounded-2xl shadow-sm border border-slate-200 gap-4">
                <div>
                    <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">All Patients</h2>
                    <p class="text-sm text-slate-500 mt-1">Manage and view all registered community patients.</p>
                </div>
                <div class="w-full sm:w-auto">
                    <a href="{{ route('pharmacist.patients.create') }}" 
                       class="inline-flex items-center justify-center w-full sm:w-auto px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-colors focus:ring-2 focus:ring-offset-2 focus:ring-blue-600">
                        <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Register New Patient
                    </a>
                </div>
            </div>

            <!-- TABLE CONTAINER -->
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden flex flex-col">

                <!-- TABLE HEADER -->
                <div class="px-6 py-5 border-b border-slate-100 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <h3 class="font-bold text-base text-slate-800 flex items-center gap-2">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        Patient Directory
                    </h3>
                    <form method="GET" action="{{ route('pharmacist.patients.index') }}" class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
                        <label for="patient-search" class="sr-only">Search patients</label>
                        <input id="patient-search" type="search" name="search" value="{{ $search }}" placeholder="Search patient name, email, or ID..." class="w-full sm:w-72 rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-200">
                        <div class="flex gap-2">
                            <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-sm font-bold transition-colors">Search</button>
                            @if($search)
                                <a href="{{ route('pharmacist.patients.index') }}" class="px-4 py-2 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-sm font-bold transition-colors">Clear</a>
                            @endif
                        </div>
                    </form>
                </div>

                <!-- RESPONSIVE TABLE -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left whitespace-nowrap">
                        
                        <!-- HEADER -->
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 uppercase text-[11px] font-bold tracking-wider">
                            <tr>
                                <th scope="col" class="py-3 px-6">Patient</th>
                                <th scope="col" class="py-3 px-6">Email</th>
                                <th scope="col" class="py-3 px-6">Info</th>
                                <th scope="col" class="py-3 px-6">BMI</th>
                                <th scope="col" class="py-3 px-6">Face Status</th>
                                <th scope="col" class="py-3 px-6 text-right">Actions</th>
                            </tr>
                        </thead>

                        <!-- BODY -->
                        <tbody class="divide-y divide-slate-100">
                            @forelse($patients as $pt)
                            <tr class="hover:bg-slate-50/70 transition-colors">

                                <!-- NAME & AVATAR -->
                                <td class="py-4 px-6 flex items-center gap-4">
                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($pt->user->name) }}&background=eff6ff&color=1d4ed8"
                                         class="w-10 h-10 rounded-full ring-2 ring-white shadow-sm object-cover" alt="{{ $pt->user->name }}">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-slate-900">{{ $pt->user->name }}</span>
                                        <span class="text-xs text-slate-400 mt-0.5">ID: {{ $pt->id }}</span>
                                        <span class="text-[11px] text-blue-600 font-medium mt-0.5">
                                            Pharmacist: {{ $pt->pharmacist?->name ?? 'Unassigned' }}
                                        </span>
                                    </div>
                                </td>

                                <!-- EMAIL -->
                                <td class="py-4 px-6 text-slate-600">
                                    {{ $pt->user->email }}
                                </td>

                                <!-- DEMOGRAPHIC -->
                                <td class="py-4 px-6 text-slate-600 capitalize">
                                    {{ $pt->gender }}, {{ $pt->age }} yrs
                                </td>

                                <!-- BMI -->
                                <td class="py-4 px-6">
                                    @if($pt->bmi >= 25)
                                        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-semibold bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20">
                                            {{ number_format($pt->bmi, 1) }}
                                        </span>
                                    @elseif($pt->bmi < 18.5)
                                        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-semibold bg-yellow-50 text-yellow-700 ring-1 ring-inset ring-yellow-600/20">
                                            {{ number_format($pt->bmi, 1) }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20">
                                            {{ number_format($pt->bmi, 1) }}
                                        </span>
                                    @endif
                                </td>

                                <!-- FACE STATUS -->
                                <td class="py-4 px-6">
                                    @if($pt->face_descriptor)
                                        <div class="flex items-center gap-1.5 text-emerald-600 text-xs font-medium">
                                            <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                                            Registered
                                        </div>
                                    @else
                                        <div class="flex items-center gap-1.5 text-slate-400 text-xs font-medium">
                                            <div class="w-1.5 h-1.5 rounded-full bg-slate-300"></div>
                                            Pending
                                        </div>
                                    @endif
                                </td>

                                <!-- ACTION BUTTONS -->
                                <td class="py-4 px-6 text-right space-x-2">
                                    <!-- Profile -->
                                    <a href="{{ route('pharmacist.patients.show', $pt->id) }}"
                                       title="View Profile"
                                       class="inline-flex items-center justify-center px-3 py-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-medium rounded-lg shadow-sm transition-colors">
                                        <svg class="w-4 h-4 mr-1.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                        Profile
                                    </a>

                                    <!-- Medication -->
                                    <a href="{{ route('pharmacist.medication.index', $pt->id) }}"
                                       title="Medications"
                                       class="inline-flex items-center justify-center px-3 py-1.5 bg-indigo-50 border border-indigo-200 hover:bg-indigo-100 text-indigo-700 text-xs font-medium rounded-lg shadow-sm transition-colors">
                                        <svg class="w-4 h-4 mr-1.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                                        Med
                                    </a>

                                    <!-- Face ID -->
                                    <a href="{{ route('pharmacist.patients.show', $pt->id) }}#face-registration"
                                       title="{{ $pt->face_descriptor ? 'Update Face' : 'Register Face' }}"
                                       class="inline-flex items-center justify-center px-3 py-1.5 {{ $pt->face_descriptor ? 'bg-emerald-50 border-emerald-200 hover:bg-emerald-100 text-emerald-700' : 'bg-slate-800 border-slate-800 hover:bg-slate-700 text-white' }} text-xs font-medium rounded-lg shadow-sm transition-colors">
                                        <svg class="w-4 h-4 mr-1.5 {{ $pt->face_descriptor ? 'text-emerald-500' : 'text-slate-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8v4l3-3m6-3V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M21 8v4l-3-3m-6-3V4a1 1 0 011-1h4a1 1 0 011 1v3m-9 13v-3a1 1 0 011-1h4a1 1 0 011 1v3m-5-4h2"></path></svg>
                                        {{ $pt->face_descriptor ? 'Face' : 'Add Face' }}
                                    </a>
                                </td>

                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-500">
                                    {{ $search ? 'No matching patients found.' : 'No patients registered yet.' }}
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
