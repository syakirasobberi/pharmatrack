<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PharmaTrack</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Updated to Plus Jakarta Sans for a cleaner, modern tech feel -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="min-h-screen flex bg-slate-50">

    <!-- Left Side: Branding & Medical Theme -->
    <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden flex-col justify-between p-12 text-white">        
        
        <div class="absolute inset-0 z-0 bg-cover bg-center bg-no-repeat" style="background-image: url('{{ asset('storage/login.png') }}');"></div>        
        <!-- Shifted to a teal/slate gradient for a clinical look -->
        <div class="absolute inset-0 bg-gradient-to-b from-teal-800/95 to-slate-900/95 z-0"></div>

        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <!-- Medical cross icon -->
                <svg class="w-10 h-10 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Pharma<span class="text-teal-400">Track</span></h1>
                    <p class="text-sm text-teal-100 mt-1 tracking-wide uppercase font-semibold">Clinical Decision Support System</p>
                </div>
            </div>

            <div class="mt-20 max-w-md">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-teal-500/20 text-teal-100 text-sm font-medium mb-6 border border-teal-500/30">
                    <span class="w-2 h-2 rounded-full bg-teal-400 animate-pulse"></span>
                    Secure Provider Portal
                </div>
                <h2 class="text-4xl font-bold leading-tight mb-4">
                    Empowering <br>
                    Community <span class="text-teal-400">Pharmacists.</span>
                </h2>
                <p class="text-slate-300 text-lg leading-relaxed">
                    Access intelligent clinical tools, manage patient health records securely, and elevate your pharmacy's standard of care.
                </p>
            </div>
        </div>

        <div class="relative z-20 flex justify-start pb-4">
            <div class="flex items-center gap-3 bg-white/5 backdrop-blur-md px-5 py-3 rounded-2xl border border-white/10">
                <svg class="w-5 h-5 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                <span class="text-sm font-medium text-slate-200">Protected with enterprise-grade clinical security</span>
            </div>
        </div>
    </div>

    <!-- Right Side: Login Form -->
    <div class="w-full lg:w-1/2 flex items-center justify-center relative bg-white px-8 py-12 lg:px-16">

        <div class="w-full max-w-md">
            <div class="mb-8">
                <a href="{{ route('welcome') }}"
                   class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-teal-700 transition-colors">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    Back to Kiosk
                </a>
            </div>

            <div class="mb-10 text-center lg:text-left">
                <div class="inline-flex items-center justify-center w-12 h-12 bg-teal-50 rounded-xl text-teal-600 mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                </div>
                <h2 class="text-3xl font-bold text-slate-900">Provider Login</h2>
                <p class="text-slate-500 mt-2">Sign in to your clinical dashboard</p>
            </div>

            <x-auth-session-status class="mb-4" :status="session('status')" />

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-semibold text-slate-700 mb-1.5">Email Address</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        </div>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" 
                            class="block w-full pl-10 pr-4 py-3 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-all sm:text-sm" placeholder="e.g. pharmacist@pharmatrack.com">
                    </div>
                    <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-500 text-sm" />
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-slate-700 mb-1.5">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        </div>
                        <input id="password" type="password" name="password" required autocomplete="current-password" 
                            class="block w-full pl-10 pr-10 py-3 rounded-lg border border-slate-200 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-all sm:text-sm" placeholder="••••••••••••">
                        
                       <button type="button" id="togglePassword"
                       class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition-colors">
                       <svg id="eyeIcon" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                       </svg>
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-500 text-sm" />
                </div>

                <div class="flex items-center justify-between pt-2">
                    <label for="remember_me" class="flex items-center cursor-pointer">
                        <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-teal-600 shadow-sm focus:ring-teal-500 w-4 h-4 cursor-pointer" name="remember">
                        <span class="ml-2 text-sm font-medium text-slate-600">Remember me</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a class="text-sm font-semibold text-teal-600 hover:text-teal-700 transition-colors" href="{{ route('password.request') }}">
                            Forgot password?
                        </a>
                    @endif
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full flex justify-center items-center gap-2 py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-teal-600 hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 transition-all">
                        Secure Login
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                </div>
            </form>

        </div>
    </div>
</body>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const password = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');

    togglePassword.addEventListener('click', function () {
        if (password.type === 'password') {
            password.type = 'text';
        } else {
            password.type = 'password';
        }
    });
});
</script>
</html>