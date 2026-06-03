<x-app-layout>
    <x-slot name="header">
        <div class="hidden"></div>
    </x-slot>

    <div class="py-8 bg-gray-50 min-h-screen flex items-center justify-center">
        <div class="max-w-4xl w-full mx-auto px-4 sm:px-6 lg:px-8">
            
            <a href="{{ route('pharmacist.patients.show', $patient->id) }}" class="inline-flex items-center text-gray-500 hover:text-blue-600 font-bold mb-6 transition-colors">
                &larr; Back to Patient Profile
            </a>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-purple-50/50 border-b border-gray-100 p-6">
                    <h2 class="text-2xl font-extrabold text-gray-800">Update Medical Records</h2>
                    <p class="text-sm text-gray-500 mt-1">Patient: <strong class="text-purple-700">{{ $patient->user->name }}</strong></p>
                </div>

                <form action="{{ route('pharmacist.patients.medical.update', $patient->id) }}" method="POST" class="p-8">
                    @csrf

                    <h3 class="text-lg font-extrabold text-gray-800 mb-4 border-b pb-2">📋 Medical History</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
                        
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Hypertension Status</label>
                            <select name="hypertension" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 bg-gray-50">
                                <option value="None" {{ optional($patient->medicalHistory)->hypertension == 'None' ? 'selected' : '' }}>None</option>
                                <option value="Monitored" {{ optional($patient->medicalHistory)->hypertension == 'Monitored' ? 'selected' : '' }}>Monitored</option>
                                <option value="High Risk" {{ optional($patient->medicalHistory)->hypertension == 'High Risk' ? 'selected' : '' }}>High Risk</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Diabetes Status</label>
                            <select name="diabetes" class="w-full rounded-xl border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 bg-gray-50">
                                <option value="None" {{ optional($patient->medicalHistory)->diabetes == 'None' ? 'selected' : '' }}>None</option>
                                <option value="Type 1" {{ optional($patient->medicalHistory)->diabetes == 'Type 1' ? 'selected' : '' }}>Type 1</option>
                                <option value="Type 2 (Controlled)" {{ optional($patient->medicalHistory)->diabetes == 'Type 2 (Controlled)' ? 'selected' : '' }}>Type 2 (Controlled)</option>
                                <option value="Type 2 (Uncontrolled)" {{ optional($patient->medicalHistory)->diabetes == 'Type 2 (Uncontrolled)' ? 'selected' : '' }}>Type 2 (Uncontrolled)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">General Allergies</label>
                            <input type="text" name="allergies" value="{{ optional($patient->medicalHistory)->allergies }}" placeholder="e.g. Dust, Seafood, Peanuts" 
                                class="w-full rounded-xl border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Drug Allergies</label>
                            <input type="text" name="drug_allergies" value="{{ optional($patient->medicalHistory)->drug_allergies }}" placeholder="e.g. Penicillin, Aspirin" 
                                class="w-full rounded-xl border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                        </div>
                    </div>

                   

                    <div class="pt-6 border-t border-gray-100 flex justify-end gap-3">
                        <a href="{{ route('pharmacist.patients.show', $patient->id) }}" 
                           class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition-colors">
                            Cancel
                        </a>
                        <button type="submit" 
                                class="px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl shadow-md transition-colors">
                            Save Medical Records
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</x-app-layout>
