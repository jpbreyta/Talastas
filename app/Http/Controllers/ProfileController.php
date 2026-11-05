<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function show()
    {
        return view('profile.account', ['user' => Auth::user()]);
    }

    public function update(Request $request)
    { 
        $user = Auth::user();
        if (! ($user instanceof User)) {
            throw new AuthenticationException;
        }

        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone'         => ['nullable', 'string', 'max:20'],
            'department'    => ['nullable', 'string', 'max:255'],
            'position'      => ['nullable', 'string', 'max:255'],
            'current_password'  => ['nullable', 'string'],
            'new_password'      => ['nullable', 'confirmed', Password::min(8)],
        ]);

        // Verify current password if changing password
        if ($request->filled('new_password')) {
            if (! Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'Current password is incorrect.'])->withInput();
            }
            $user->password = Hash::make($validated['new_password']);
        }

        $user->name       = $validated['name'];
        $user->email      = $validated['email'];
        $user->phone      = $validated['phone'] ?? null;
        $user->department = $validated['department'] ?? null;
        $user->position   = $validated['position'] ?? null;
        $user->save();

        return back()->with('success', 'Profile updated successfully.');
    }

    public function support()
    {
        return view('profile.support');
    }

    public function submitSupport(Request $request)
    {
        $request->validate([
            'subject'  => ['required', 'string', 'max:255'],
            'category' => ['required', 'string'],
            'message'  => ['required', 'string', 'min:20'],
        ]);

        // In a real system this would send an email / create a ticket
        return back()->with('support_sent', 'Your support request has been submitted. Our team will respond within 1–2 business days.');
    }
}
