<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Set a new password') }} - PharmaTrack</title>
    
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
</head>
<body class="font-sans antialiased text-gray-900 bg-gray-50">
    <div class="min-h-screen flex items-center justify-center p-4">
        
        <div class="w-full max-w-md bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-teal-900 p-6 text-center text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-teal-600 rounded-full mix-blend-multiply filter blur-2xl opacity-40 transform translate-x-1/2 -translate-y-1/2"></div>
                <h1 class="relative z-10"><x-brand theme="dark" :size="44" /></h1>
            </div>

            <div class="p-8">
                <div style="display:flex;justify-content:flex-end;margin-bottom:12px;">
                    <x-lang-toggle />
                </div>

                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ __('Welcome!') }}</h2>
                    <p class="text-gray-500">{{ __('Since this is your first time logging in, please set a secure personal password.') }}</p>
                </div>

                <form method="POST" action="{{ route('password.force-change.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">{{ __('New password') }}</label>
                        <input id="password" type="password" name="password" required autofocus autocomplete="new-password" 
                            class="block w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-teal-500 focus:border-teal-500 transition-colors shadow-sm" placeholder="{{ __('At least 8 characters') }}">
                        <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-600 text-sm" />
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">{{ __('Confirm new password') }}</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" 
                            class="block w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-teal-500 focus:border-teal-500 transition-colors shadow-sm" placeholder="{{ __('Repeat your new password') }}">
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2 text-red-600 text-sm" />
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-lg shadow-md text-sm font-bold text-white bg-teal-700 hover:bg-teal-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-600 transition-all transform hover:-translate-y-0.5">
                            {{ __('Save Password & Continue') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
    </div>
</body>
</html>
