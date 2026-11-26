{{-- Step-by-step guide shown next to the face registration camera (Register Patient + Update Face). --}}
<div class="w-full max-w-xl rounded-xl border border-teal-100 bg-teal-50/60 p-4 text-left text-sm text-slate-700">
    <p class="font-bold text-teal-900 mb-2">{{ __('How to register the patient\'s face') }}</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
        <div class="rounded-lg bg-white/80 p-3 border border-teal-100">
            <p class="text-xs font-bold uppercase tracking-wide text-teal-500 mb-1">{{ __('Before you start') }}</p>
            <ul class="list-disc pl-4 space-y-1 text-xs">
                <li>{{ __('Good light on the face (no bright window behind).') }}</li>
                <li>{{ __('Remove sunglasses, mask and cap.') }}</li>
                <li>{!! __('Only <strong>one person</strong> in front of the camera.') !!}</li>
                <li>{{ __('Sit about an arm\'s length away (50–80 cm).') }}</li>
            </ul>
        </div>
        <div class="rounded-lg bg-white/80 p-3 border border-teal-100">
            <p class="text-xs font-bold uppercase tracking-wide text-teal-500 mb-1">{{ __('Box colour') }}</p>
            <ul class="space-y-1 text-xs">
                <li><span class="inline-block w-3 h-3 rounded-sm border-2 border-sky-500 align-middle mr-1"></span>{{ __('Blue box around the face = face found, ready.') }}</li>
                <li>{{ __('No box = move into the frame and look at the camera.') }}</li>
            </ul>
        </div>
    </div>

    <ol class="space-y-2">
        <li class="flex gap-2"><span class="flex-none w-5 h-5 rounded-full bg-teal-600 text-white text-xs font-bold flex items-center justify-center">1</span>
            <span>{!! __('Click <strong>Start Camera</strong> and allow camera access if the browser asks.') !!}</span></li>
        <li class="flex gap-2"><span class="flex-none w-5 h-5 rounded-full bg-teal-600 text-white text-xs font-bold flex items-center justify-center">2</span>
            <span>{!! __('Patient looks <strong>straight at the camera</strong> and waits for the blue box.') !!}</span></li>
        <li class="flex gap-2"><span class="flex-none w-5 h-5 rounded-full bg-teal-600 text-white text-xs font-bold flex items-center justify-center">3</span>
            <span>{!! __('Click <strong>Capture</strong>. Patient <strong>holds still with eyes open</strong> for about 3 seconds — no talking, no turning. The bar fills 5/5.') !!}</span></li>
        <li class="flex gap-2"><span class="flex-none w-5 h-5 rounded-full bg-teal-600 text-white text-xs font-bold flex items-center justify-center">4</span>
            <span>{!! __('If a hint appears (<em>Move closer</em>, <em>Face the camera</em>, <em>Improve lighting</em>), follow it — capture continues automatically.') !!}</span></li>
    </ol>

    <p class="mt-3 text-xs text-teal-900 bg-white/80 border border-teal-100 rounded-lg p-2">
        ↔ {!! __('<strong>No head-turning needed here.</strong> That step is only at the kiosk: when the patient checks in, the kiosk asks them to <strong>turn their head to the side shown until they hear a beep, then look back</strong> — this proves they are a real person and not a photo. Tell the patient about this so they know what to expect.') !!}
    </p>

    {{-- Filled by FaceUtils.captureAverageDescriptor() progress --}}
    <div id="capture-progress" class="hidden mt-3">
        <div class="flex justify-between text-xs font-bold text-teal-900 mb-1">
            <span id="capture-progress-label">{{ __('Capturing…') }}</span>
            <span id="capture-progress-count">0/5</span>
        </div>
        <div class="h-2 w-full rounded-full bg-teal-100 overflow-hidden">
            <div id="capture-progress-bar" class="h-full bg-teal-600 transition-all duration-200" style="width:0%"></div>
        </div>
    </div>
</div>
