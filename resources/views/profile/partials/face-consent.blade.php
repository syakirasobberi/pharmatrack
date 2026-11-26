{{-- Patient's own view of their face-recognition consent (PDPA), with withdrawal. --}}
@php $facePatient = \App\Models\Patient::where('user_id', $user->id)->first(); @endphp

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">Face Recognition</h2>
        <p class="mt-1 text-sm text-gray-600">{{ \App\Support\FaceConsent::PATIENT_SUMMARY }}</p>
    </header>

    @if (session('face_status'))
        <p class="mt-4 rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm font-semibold text-green-800">{{ session('face_status') }}</p>
    @endif

    @if ($facePatient?->hasFace())
        <p class="mt-4 text-sm text-gray-800">
            <strong>Status: on.</strong>
            @if ($facePatient->face_consent_at)
                You agreed on {{ $facePatient->face_consent_at->format('d M Y') }}.
            @else
                Your face was registered before consent was recorded; your pharmacist will confirm it with you.
            @endif
        </p>

        {{-- Two steps without a browser dialog: open, then confirm. --}}
        <details class="mt-4">
            <summary class="inline-flex cursor-pointer list-none items-center rounded-md border border-red-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-red-700 hover:bg-red-50">
                Withdraw consent and delete my face data
            </summary>
            <form method="POST" action="{{ route('patient.face.withdraw') }}" class="mt-3 rounded-xl border border-red-200 bg-red-50 p-4">
                @csrf
                @method('DELETE')
                <p class="text-sm text-red-800">Your face data will be deleted now. The kiosk will no longer recognise you; you can still log in with your email. You can ask your pharmacist to register your face again at any time.</p>
                <button type="submit" class="mt-3 rounded-md bg-red-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white hover:bg-red-700">Yes, delete my face data</button>
            </form>
        </details>
    @else
        <p class="mt-4 text-sm text-gray-800"><strong>Status: off.</strong> No face data is stored for you. Ask your pharmacist if you would like to use the kiosk face check-in.</p>
    @endif
</section>
