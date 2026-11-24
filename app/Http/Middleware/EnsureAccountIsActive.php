<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user?->isActive(), 403, 'La cuenta está desactivada.');

        if ($request->hasSession() && $request->session()->get(User::AUTH_VERSION_SESSION_KEY) !== $user->auth_version) {
            $guard = Auth::guard('web');
            if ($guard->viaRemember()) {
                $request->session()->put(User::AUTH_VERSION_SESSION_KEY, $user->auth_version);
            } else {
                $guard->logoutCurrentDevice();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return $request->expectsJson()
                    ? response()->json(['message' => 'La sesión venció. Inicia sesión de nuevo.'], 401)
                    : redirect()->route('login')->withErrors(['email' => 'La sesión venció. Inicia sesión de nuevo.']);
            }
        }

        return $next($request);
    }
}
