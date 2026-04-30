<x-app-layout>
    <x-slot name="header">
        <div class="hidden"></div>
    </x-slot>

    <div class="py-8 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- HEADER -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
                <div>
                    <h2 class="text-2xl font-extrabold text-gray-800">All Patients</h2>
                    <p class="text-sm text-gray-500 mt-1">Manage and view all registered community patients.</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <a href="{{ route('pharmacist.patients.create') }}" 
                       class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-md">
                        + Register New Patient
                    </a>
                </div>
            </div>

            <!-- TABLE -->
            <div class="bg-white border border-gray-200 rounded-3xl shadow-md overflow-hidden">

    <!-- HEADER -->
    <div class="p-6 bg-gradient-to-r from-blue-50 to-white border-b border-gray-100">
        <h3 class="font-bold text-lg text-gray-700 flex items-center gap-2">
            Patient Directory
        </h3>
    </div>

    <!-- TABLE -->
    <div class="overflow-x-auto">
        <table class="w-full text-sm">

            <!-- HEADER -->
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wider">
                <tr>
                    <th class="py-4 px-6 text-left">Patient</th>
                    <th class="py-4 px-6 text-left">Email</th>
                    <th class="py-4 px-6 text-left">Info</th>
                    <th class="py-4 px-6 text-left">BMI</th>
                    <th class="py-4 px-6 text-left">Face</th>
                    <th class="py-4 px-6 text-right">Action</th>
                </tr>
            </thead>

            <!-- BODY -->
            <tbody class="divide-y">

                @foreach($patients as $pt)
                <tr class="hover:bg-blue-50/40 transition">

                    <!-- NAME -->
                    <td class="py-4 px-6 flex items-center gap-3">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode($pt->user->name) }}&background=eff6ff&color=1d4ed8"
                             class="w-10 h-10 rounded-full border shadow-sm">

                        <div>
                            <p class="font-semibold text-gray-800">
                                {{ $pt->user->name }}
                            </p>
                            <p class="text-xs text-gray-400">
                                ID: {{ $pt->id }}
                            </p>
                        </div>
                    </td>

                    <!-- EMAIL -->
                    <td class="py-4 px-6 text-gray-600">
                        {{ $pt->user->email }}
                    </td>

                    <!-- DEMOGRAPHIC -->
                    <td class="py-4 px-6 text-gray-600">
                        {{ $pt->gender }}, {{ $pt->age }} yrs
                    </td>

                    <!-- BMI -->
                    <td class="py-4 px-6">
                        @if($pt->bmi >= 25)
                            <span class="px-3 py-1 bg-orange-100 text-orange-600 text-xs rounded-full font-semibold">
                                ⚠️ {{ number_format($pt->bmi,1) }}
                            </span>
                        @elseif($pt->bmi < 18.5)
                            <span class="px-3 py-1 bg-yellow-100 text-yellow-600 text-xs rounded-full font-semibold">
                                ⚠️ {{ number_format($pt->bmi,1) }}
                            </span>
                        @else
                            <span class="px-3 py-1 bg-green-100 text-green-600 text-xs rounded-full font-semibold">
                                ✅ {{ number_format($pt->bmi,1) }}
                            </span>
                        @endif
                    </td>

                    <!-- FACE STATUS -->
                    <td class="py-4 px-6">
                        @if($pt->face_descriptor)
                            <span class="flex items-center gap-1 text-green-600 font-semibold text-xs">
                                ● Registered
                            </span>
                        @else
                            <span class="flex items-center gap-1 text-red-500 font-semibold text-xs">
                                ● Not Registered
                            </span>
                        @endif
                    </td>

                    <!-- ACTION -->
                    <td class="py-4 px-6 text-right space-x-2">

                        <a href="{{ route('pharmacist.patients.show', $pt->id) }}"
                           class="px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-xs rounded-lg shadow">
                            Profile
                        </a>

                        <a href="{{ route('pharmacist.medication.index', $pt->id) }}"
                           class="px-3 py-1.5 bg-purple-500 hover:bg-purple-600 text-black text-xs rounded-lg shadow">
                            Med
                        </a>

                        <a href="/register-face/{{ $pt->id }}"
                           class="px-3 py-1.5 bg-green-500 hover:bg-green-600 text-white text-xs rounded-lg shadow">
                            Face
                        </a>

                    </td>

                </tr>
                @endforeach

            </tbody>
        </table>
    </div>
</div>

        </div>
    </div>
</x-app-layout>