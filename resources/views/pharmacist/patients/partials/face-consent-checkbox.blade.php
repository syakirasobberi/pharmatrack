{{-- PDPA consent before a face is captured. The server also refuses a face without it.
     $name: form field name (default "face_consent"). --}}
<label class="mt-3 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 cursor-pointer">
    <input type="checkbox" name="{{ $name ?? 'face_consent' }}" value="1" data-face-consent
           @checked(old($name ?? 'face_consent'))
           class="mt-0.5 h-4 w-4 rounded border-amber-400 text-teal-600 focus:ring-teal-500">
    <span>
        <strong>{{ __('Patient consent (required).') }}</strong>
        {{ __('I have explained this to the patient and they agree:') }} {{ __(\App\Support\FaceConsent::STATEMENT) }}
    </span>
</label>
@error($name ?? 'face_consent')
    <p class="mt-1 text-sm font-semibold text-red-600">{{ $message }}</p>
@enderror
