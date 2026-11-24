<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(\Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse
        {
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'lowercase', 'email', 'max:255', \Illuminate\Validation\Rule::unique(\App\Models\User::class)],
                'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
            ]);

            $user = \App\Models\User::create([
                'id_usuario' => (string) \Illuminate\Support\Str::uuid(), // Genera un UUID para el campo 'id_usuario'
                'nombre' => $request->name, // Mapea el campo 'name' del formulario al campo 'nombre' de la base de datos
                'email' => $request->email,
                'password' => \Illuminate\Support\Facades\Hash::make($request->password),
                'telefono' => null, // La cuenta web no suplanta la identidad telefónica de despacho.
                'rol' => 'PASAJERO', // El registro público nunca concede privilegios administrativos.
            ]);

            event(new \Illuminate\Auth\Events\Registered($user));

            \Illuminate\Support\Facades\Auth::login($user);

            return redirect(route('dashboard', absolute: false));
        }
}
