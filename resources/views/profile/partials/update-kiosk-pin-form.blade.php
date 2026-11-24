<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Kiosk PIN') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('A 6–12 digit PIN you enter on the patient kiosk to approve record updates. Keep it different from your login password.') }}
        </p>

        <p class="mt-1 text-sm {{ $user->kiosk_pin ? 'text-emerald-600' : 'text-amber-600' }}">
            {{ $user->kiosk_pin ? __('A kiosk PIN is set.') : __('No kiosk PIN set yet. Kiosk updates for your patients are blocked until you set one.') }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.kiosk-pin.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <x-input-label for="kiosk_pin_current_password" :value="__('Current Password')" />
            <x-text-input id="kiosk_pin_current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
            <x-input-error :messages="$errors->updateKioskPin->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="kiosk_pin" :value="__('New Kiosk PIN')" />
            <x-text-input id="kiosk_pin" name="kiosk_pin" type="password" inputmode="numeric" class="mt-1 block w-full" autocomplete="off" />
            <x-input-error :messages="$errors->updateKioskPin->get('kiosk_pin')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="kiosk_pin_confirmation" :value="__('Confirm Kiosk PIN')" />
            <x-text-input id="kiosk_pin_confirmation" name="kiosk_pin_confirmation" type="password" inputmode="numeric" class="mt-1 block w-full" autocomplete="off" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save PIN') }}</x-primary-button>

            @if (session('status') === 'kiosk-pin-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
