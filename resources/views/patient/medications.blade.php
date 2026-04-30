<x-app-layout>
    <x-slot name="header">My Medications</x-slot>

    <div class="py-10 min-h-screen" style="background:#f1f5f9;">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            {{-- Page Title --}}
            <div style="background:linear-gradient(135deg,#4f46e5,#7c3aed);border-radius:1.5rem;padding:2rem 2.5rem;color:#fff;position:relative;overflow:hidden;">
                <div style="position:absolute;top:-30px;right:-30px;width:160px;height:160px;background:rgba(255,255,255,0.08);border-radius:50%;"></div>
                <p style="font-size:.75rem;font-weight:700;letter-spacing:.15em;text-transform:uppercase;color:#c4b5fd;margin-bottom:.25rem;">Your Active Prescriptions</p>
                <h1 style="font-size:2rem;font-weight:900;margin:0 0 .5rem;">Medication Schedule</h1>
                <p style="color:#ddd6fe;font-size:.95rem;">Keep track of your medications and dosage instructions below.</p>
            </div>

            {{-- Reminder Banner --}}
            <div style="background:#fef3c7;border:1px solid #fde68a;border-radius:1rem;padding:1rem 1.5rem;display:flex;align-items:center;gap:.75rem;">
                <span style="font-size:1.5rem;">⏰</span>
                <div>
                    <p style="font-weight:700;color:#92400e;margin:0;">Medication Reminder</p>
                    <p style="color:#b45309;font-size:.875rem;margin:0;">Always take your medication as prescribed. Contact your pharmacist if you have any questions.</p>
                </div>
            </div>

            {{-- Medications List --}}
            @if($patient && $patient->medications->count() > 0)
                <div class="space-y-4">
                    @foreach($patient->medications as $index => $med)
                        @php
                            $colors = [
                                ['bg'=>'#ede9fe','border'=>'#c4b5fd','icon'=>'#7c3aed','badge_bg'=>'#7c3aed'],
                                ['bg'=>'#ecfdf5','border'=>'#6ee7b7','icon'=>'#059669','badge_bg'=>'#059669'],
                                ['bg'=>'#eff6ff','border'=>'#93c5fd','icon'=>'#2563eb','badge_bg'=>'#2563eb'],
                                ['bg'=>'#fdf4ff','border'=>'#e879f9','icon'=>'#a21caf','badge_bg'=>'#a21caf'],
                                ['bg'=>'#fff7ed','border'=>'#fdba74','icon'=>'#ea580c','badge_bg'=>'#ea580c'],
                            ];
                            $color = $colors[$index % count($colors)];
                        @endphp
                        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:1.25rem;overflow:hidden;box-shadow:0 1px 4px rgba(0,0,0,.06);">
                            <div style="display:flex;align-items:stretch;">
                                {{-- Color accent stripe --}}
                                <div style="width:6px;background:{{ $color['icon'] }};flex-shrink:0;border-radius:1.25rem 0 0 1.25rem;"></div>

                                <div style="flex:1;padding:1.25rem 1.5rem;display:flex;flex-wrap:wrap;align-items:center;gap:1rem;">
                                    {{-- Icon --}}
                                    <div style="width:3rem;height:3rem;border-radius:.875rem;background:{{ $color['bg'] }};border:1px solid {{ $color['border'] }};display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0;">
                                        💊
                                    </div>

                                    {{-- Info --}}
                                    <div style="flex:1;min-width:180px;">
                                        <h3 style="font-size:1.1rem;font-weight:800;color:#1e293b;margin:0 0 .25rem;">{{ $med->name }}</h3>
                                        <p style="font-size:.85rem;color:#64748b;margin:0;">
                                            Dosage: <strong style="color:#334155;">{{ $med->dosage ?: 'Not specified' }}</strong>
                                        </p>
                                        @if($med->notes || $med->frequency)
                                            <p style="font-size:.85rem;color:#64748b;margin:.25rem 0 0;">
                                                Instructions: <strong style="color:#334155;">{{ $med->frequency ?? $med->notes }}</strong>
                                            </p>
                                        @endif
                                    </div>

                                    {{-- Dates --}}
                                    <div style="text-align:right;flex-shrink:0;">
                                        @if($med->start_date)
                                            <p style="font-size:.75rem;color:#94a3b8;margin:0;">Started</p>
                                            <p style="font-size:.9rem;font-weight:700;color:#475569;margin:0;">{{ \Carbon\Carbon::parse($med->start_date)->format('d M Y') }}</p>
                                        @endif
                                        @if($med->end_date)
                                            <p style="font-size:.75rem;color:#94a3b8;margin:.5rem 0 0;">Until</p>
                                            <p style="font-size:.9rem;font-weight:700;color:#475569;margin:0;">{{ \Carbon\Carbon::parse($med->end_date)->format('d M Y') }}</p>
                                        @endif
                                        @if(!$med->start_date && !$med->end_date)
                                            <span style="background:{{ $color['badge_bg'] }};color:#fff;font-size:.7rem;font-weight:700;padding:.2rem .65rem;border-radius:9999px;">Active</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div style="background:#fff;border:2px dashed #cbd5e1;border-radius:1.5rem;padding:4rem;text-align:center;">
                    <div style="font-size:3.5rem;margin-bottom:1rem;">💊</div>
                    <h3 style="font-size:1.25rem;font-weight:800;color:#334155;margin-bottom:.5rem;">No Medications on Record</h3>
                    <p style="color:#64748b;font-size:.9rem;">Your pharmacist hasn't recorded any medications for you yet.</p>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
