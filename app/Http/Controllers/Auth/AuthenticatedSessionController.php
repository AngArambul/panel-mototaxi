<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();
        $request->session()->put(User::AUTH_VERSION_SESSION_KEY, $request->user()->auth_version);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        if ($request->session()->has('dispatch_token')) {
            try {
                $token = \Illuminate\Support\Facades\Crypt::decryptString($request->session()->get('dispatch_token'));
                \Illuminate\Support\Facades\Http::withToken($token)->timeout(3)->post(rtrim(config('services.dispatch.url'), '/').'/api/auth/logout');
            } catch (\Throwable $exception) {
                // Invalidate local session even when Node is offline; its token expires independently.
            }
        }
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
