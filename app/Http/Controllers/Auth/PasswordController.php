<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed', 'max:72'],
        ]);

        $user = $request->user();
        DB::transaction(function () use ($user, $validated) {
            $user->password = Hash::make($validated['password']);
            $user->revokeAccess();
        });
        $request->session()->regenerate();
        $request->session()->put(User::AUTH_VERSION_SESSION_KEY, $user->auth_version);

        return back()->with('status', 'password-updated');
    }
}
