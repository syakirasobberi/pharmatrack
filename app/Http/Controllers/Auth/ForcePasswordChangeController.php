<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ForcePasswordChangeController extends Controller
{
    /**
     * Show the forced password change view.
     */
    public function create(Request $request)
    {
        // If they don't require a change, redirect them away
        if (! $request->user()->requires_password_change) {
            return redirect()->route('dashboard'); // Or their respective dashboard based on role
        }

        return view('auth.force-password-change');
    }

    /**
     * Handle the forced password change request.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
            'requires_password_change' => false,
        ]);

        // Redirect based on role
        if ($request->user()->role === 'patient') {
            return redirect()->route('patient.dashboard')->with('status', 'password-changed');
        } elseif ($request->user()->role === 'pharmacist') {
            return redirect()->route('pharmacist.dashboard')->with('status', 'password-changed');
        } elseif ($request->user()->role === 'admin') {
            return redirect()->route('admin.dashboard')->with('status', 'password-changed');
        }

        return redirect()->route('dashboard')->with('status', 'password-changed');
    }
}
