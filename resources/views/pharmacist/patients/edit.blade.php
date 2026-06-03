<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Patient Profile') }}
        </h2>
    </x-slot>

    <div class="py-10 bg-gray-50 min-h-screen">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-6">
                <a href="{{ route('pharmacist.patients.show', $patient->id) }}" class="inline-flex items-center text-gray-500 hover:text-blue-700 font-bold transition-colors">
                    &larr; Back to Full PRP File
                </a>
            </div>

            <div class="bg-white border border-gray-200 shadow-sm rounded-2xl overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-extrabold text-gray-800">Patient Details</h3>
                    <p class="text-sm text-gray-500 mt-1">Update the patient profile information used across the pharmacist records.</p>
                </div>

                <form action="{{ route('pharmacist.patients.update', $patient->id) }}" method="POST" class="p-6 space-y-8">
                    @csrf
                    @method('PATCH')

                    @if ($errors->any())
                        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                            <p class="font-bold mb-2">Please check the fields below.</p>
                            <ul class="list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label for="name" class="block text-sm font-bold text-gray-700">Full Name</label>
                            <input id="name" type="text" name="name" value="{{ old('name', $patient->user->name) }}" required class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-bold text-gray-700">Email Address</label>
                            <input id="email" type="email" name="email" value="{{ old('email', $patient->user->email) }}" required class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label for="age" class="block text-sm font-bold text-gray-700">Age</label>
                            <input id="age" type="number" name="age" min="0" max="130" value="{{ old('age', $patient->age) }}" required class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div>
                            <label for="gender" class="block text-sm font-bold text-gray-700">Gender</label>
                            <select id="gender" name="gender" required class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="Male" @selected(old('gender', $patient->gender) === 'Male')>Male</option>
                                <option value="Female" @selected(old('gender', $patient->gender) === 'Female')>Female</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-extrabold text-gray-800 border-b border-gray-100 pb-3 mb-5">Health Measurements</h3>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                            <div>
                                <label for="height" class="block text-sm font-bold text-gray-700">Height (cm)</label>
                                <input id="height" type="number" step="0.01" min="1" name="height" value="{{ old('height', $patient->height) }}" required class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div>
                                <label for="weight" class="block text-sm font-bold text-gray-700">Weight (kg)</label>
                                <input id="weight" type="number" step="0.01" min="1" name="weight" value="{{ old('weight', $patient->weight) }}" required class="mt-1 block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div>
                                <label for="bmi_display" class="block text-sm font-bold text-gray-700">Calculated BMI</label>
                                <input id="bmi_display" type="text" readonly value="{{ number_format($patient->bmi, 2) }}" class="mt-1 block w-full bg-gray-100 border-gray-300 rounded-lg shadow-sm cursor-not-allowed text-blue-700 font-bold">
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-2">
                        <a href="{{ route('pharmacist.patients.show', $patient->id) }}" class="px-6 py-2.5 bg-gray-200 text-gray-800 text-center font-bold rounded-lg hover:bg-gray-300 transition-colors">
                            Cancel
                        </a>
                        <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const heightInput = document.getElementById('height');
            const weightInput = document.getElementById('weight');
            const bmiDisplay = document.getElementById('bmi_display');

            function calculateBMI() {
                const heightCm = parseFloat(heightInput.value);
                const weightKg = parseFloat(weightInput.value);

                if (heightCm > 0 && weightKg > 0) {
                    const heightM = heightCm / 100;
                    bmiDisplay.value = (weightKg / (heightM * heightM)).toFixed(2);
                    return;
                }

                bmiDisplay.value = '';
            }

            heightInput.addEventListener('input', calculateBMI);
            weightInput.addEventListener('input', calculateBMI);
        });
    </script>
</x-app-layout>
