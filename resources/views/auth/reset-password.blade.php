<x-guest-layout>
    <div style="display:flex;justify-content:flex-end;margin-bottom:12px;">
        <x-lang-toggle />
    </div>

    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-900">{{ __('Set a new password') }}</h2>
        <p class="mt-1 text-sm text-gray-500">{{ __('Choose a password with at least 8 characters.') }}</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email Address')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('New password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm new password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                                type="password"
                                name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-6">
            <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-gray-900 underline">{{ __('Back to Login') }}</a>
            {{-- Own wording, not 'Reset Password': that key is also the button in Laravel's reset email. --}}
            <x-primary-button>
                {{ __('Save new password') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
