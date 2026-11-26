<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'PharmaTrack') }} - Sign-in Code</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
</head>
<body class="font-sans text-gray-900 antialiased">
<div class="min-h-screen flex flex-col bg-slate-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl w-full mx-auto flex-1 flex items-center justify-center">
        <div class="max-w-4xl w-full bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col md:flex-row">

            <div class="md:w-1/2 bg-teal-600 p-8 text-white flex flex-col justify-center items-center relative overflow-hidden">
                <div class="relative z-10 text-center">
                    <div class="inline-block mb-6">
                        <x-brand-mark :size="72" />
                    </div>
                    <h2 class="text-3xl font-bold mb-3 tracking-tight">PharmaTrack</h2>
                    <p class="text-teal-100 text-sm max-w-xs mx-auto leading-relaxed">
                        Staff accounts can open every patient record, so signing in takes two steps: your password, then a code sent to your email.
                    </p>
                </div>
            </div>

            <div class="md:w-1/2 p-8 sm:p-12 flex flex-col justify-center">
                <div class="mb-6">
                    <h3 class="text-2xl font-bold text-gray-800 mb-3">Enter your sign-in code</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">
                        We emailed a 6-digit code to <strong class="text-gray-700">{{ $email }}</strong>.
                        It expires in {{ \App\Support\TwoFactor::CODE_MINUTES }} minutes.
                    </p>
                </div>

                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('two-factor.verify') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Sign-in code</label>
                        <input id="code" type="text" name="code" required autofocus
                               inputmode="numeric" autocomplete="one-time-code" pattern="[0-9 ]*" maxlength="7"
                               class="block w-full px-4 py-3 border border-gray-300 rounded-xl text-2xl tracking-[0.5em] text-center text-gray-900 focus:ring-teal-500 focus:border-teal-500 shadow-sm"
                               placeholder="000000">
                        <x-input-error :messages="$errors->get('code')" class="mt-2 text-red-500 text-sm" />
                    </div>

                    <label class="flex items-start gap-2 text-sm text-gray-600">
                        <input type="checkbox" name="remember_device" value="1" class="mt-0.5 rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                        <span>Remember this device for {{ \App\Support\TwoFactor::REMEMBER_DAYS }} days. Only tick this on your own pharmacy computer, never on a shared one.</span>
                    </label>

                    <button type="submit" class="w-full flex justify-center items-center py-3 px-4 rounded-xl shadow-md text-sm font-semibold text-white bg-teal-600 hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500">
                        Verify and continue
                    </button>
                </form>

                <div class="mt-6 flex items-center justify-between text-sm">
                    <form method="POST" action="{{ route('two-factor.resend') }}">
                        @csrf
                        <button type="submit" class="font-medium text-teal-600 hover:text-teal-800">Send a new code</button>
                    </form>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="font-medium text-gray-500 hover:text-gray-700">Cancel and log out</button>
                    </form>
                </div>

                <p class="mt-6 text-xs text-gray-400">No email? Check your spam folder. Codes are sent from the pharmacy system address.</p>
            </div>
        </div>
    </div>

    <x-footer class="w-full text-center text-sm text-gray-400 py-4" />
</div>
</body>
</html>
