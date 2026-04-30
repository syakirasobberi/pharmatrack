<x-app-layout>
    <x-slot name="header">Account Settings</x-slot>

    <div class="py-10 min-h-screen" style="background:#f1f5f9;">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Page Header --}}
            <div style="background:linear-gradient(135deg,#4f46e5,#7c3aed);border-radius:1.5rem;padding:2rem 2.5rem;color:#fff;position:relative;overflow:hidden;">
                <div style="position:absolute;top:-30px;right:-30px;width:130px;height:130px;background:rgba(255,255,255,0.08);border-radius:50%;"></div>
                <p style="font-size:.75rem;font-weight:700;letter-spacing:.15em;text-transform:uppercase;color:#c4b5fd;margin-bottom:.25rem;">Account Settings</p>
                <h1 style="font-size:1.75rem;font-weight:900;margin:0;">Change Password</h1>
            </div>

            {{-- Success Message --}}
            @if (session('success'))
                <div style="background:#ecfdf5;border:1px solid #6ee7b7;border-radius:1rem;padding:1rem 1.5rem;display:flex;align-items:center;gap:.75rem;">
                    <span style="font-size:1.25rem;">✅</span>
                    <p style="color:#065f46;font-weight:700;margin:0;">{{ session('success') }}</p>
                </div>
            @endif

            {{-- Form --}}
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:1.5rem;padding:2rem;box-shadow:0 1px 6px rgba(0,0,0,.06);">
                <form method="POST" action="{{ route('patient.password.update') }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="current_password" style="display:block;font-size:.875rem;font-weight:600;color:#374151;margin-bottom:.5rem;">Current Password</label>
                        <input id="current_password" type="password" name="current_password" required autocomplete="current-password"
                            style="display:block;width:100%;padding:.75rem 1rem;border:1px solid #d1d5db;border-radius:.75rem;font-size:.95rem;transition:border-color .2s;box-sizing:border-box;"
                            placeholder="Enter your current password">
                        @error('current_password')
                            <p style="color:#dc2626;font-size:.8rem;margin:.4rem 0 0;">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" style="display:block;font-size:.875rem;font-weight:600;color:#374151;margin-bottom:.5rem;">New Password</label>
                        <input id="password" type="password" name="password" required autocomplete="new-password"
                            style="display:block;width:100%;padding:.75rem 1rem;border:1px solid #d1d5db;border-radius:.75rem;font-size:.95rem;transition:border-color .2s;box-sizing:border-box;"
                            placeholder="At least 8 characters">
                        @error('password')
                            <p style="color:#dc2626;font-size:.8rem;margin:.4rem 0 0;">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" style="display:block;font-size:.875rem;font-weight:600;color:#374151;margin-bottom:.5rem;">Confirm New Password</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                            style="display:block;width:100%;padding:.75rem 1rem;border:1px solid #d1d5db;border-radius:.75rem;font-size:.95rem;transition:border-color .2s;box-sizing:border-box;"
                            placeholder="Repeat your new password">
                        @error('password_confirmation')
                            <p style="color:#dc2626;font-size:.8rem;margin:.4rem 0 0;">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                        style="width:100%;padding:.875rem;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;font-weight:700;font-size:.95rem;border:none;border-radius:.875rem;cursor:pointer;transition:opacity .2s;"
                        onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">
                        Update Password
                    </button>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
