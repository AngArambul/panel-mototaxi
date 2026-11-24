<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(['email' => ['required', 'email', 'max:150']]);

        if (config('mail.default') === 'log') {
            return back()->withErrors(['email' => 'Configura un transporte de correo seguro para enviar enlaces de recuperación.']);
        }

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        try {
            Password::sendResetLink(['email' => strtolower($validated['email']), 'activo' => true]);
        } catch (TransportExceptionInterface) {
            Log::warning('mototaxi.account.password_reset_transport_unavailable');

            return back()->withErrors(['email' => 'El correo de recuperación no está disponible. Intenta más tarde.']);
        }

        return back()->with('status', 'Si el correo pertenece a una cuenta activa, recibirás las instrucciones de recuperación.');
    }
}
