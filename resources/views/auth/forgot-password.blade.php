<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'PharmaTrack') }} - Forgot Password</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-gray-900 antialiased">
<div class="min-h-screen flex flex-col bg-slate-50 py-12 px-4 sm:px-6 lg:px-8">
            
        <div class="max-w-4xl w-full mx-auto flex-1 flex items-center justify-center">
        <div class="max-w-4xl w-full bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col md:flex-row">

            <div class="md:w-1/2 bg-teal-600 p-8 text-white flex flex-col justify-center items-center relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-full opacity-10 pointer-events-none">
                    <svg class="absolute w-64 h-64 -top-12 -left-12 text-white" fill="currentColor" viewBox="0 0 100 100"><circle cx="50" cy="50" r="50"></circle></svg>
                    <svg class="absolute w-48 h-48 -bottom-10 -right-10 text-white" fill="currentColor" viewBox="0 0 100 100"><circle cx="50" cy="50" r="50"></circle></svg>
                </div>
                
                <div class="relative z-10 text-center">
                    <div class="bg-white/20 p-4 rounded-full inline-block mb-6 backdrop-blur-sm">
                        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </div>
                    <h2 class="text-3xl font-bold mb-3 tracking-tight">PharmaTrack</h2>
                    <p class="text-teal-100 text-sm max-w-xs mx-auto leading-relaxed">
                        Secure management of health records and efficient patient medication monitoring.
                    </p>
                </div>
            </div>

            <div class="md:w-1/2 p-8 sm:p-12 flex flex-col justify-center">
                
                <div class="mb-8">
                    <h3 class="text-2xl font-bold text-gray-800 mb-3">Reset Password</h3>
                    <p class="text-sm text-gray-500 leading-relaxed">
                        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
                    </p>
                </div>

                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Email Address') }}</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus 
                                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-xl text-gray-900 focus:ring-teal-500 focus:border-teal-500 sm:text-sm transition duration-200 ease-in-out shadow-sm" 
                                placeholder="pharmacist@pharmaceutical.com">
                        </div>
                        <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-500 text-sm" />
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-xl shadow-md text-sm font-semibold text-white bg-teal-600 hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 transition duration-200 ease-in-out transform hover:-translate-y-0.5">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            {{ __('Email Password Reset Link') }}
                        </button>
                    </div>
                    
                    <div class="text-center mt-6">
                        <a href="{{ route('login') }}" class="text-sm font-medium text-teal-600 hover:text-teal-800 transition duration-150 ease-in-out flex justify-center items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                            Back to Secure Login
                        </a>
                    </div>
                </form>

            </div>
        </div>
        </div>

        <x-footer class="w-full text-center text-sm text-gray-400 py-4" />
    </div>
</body>
</html>