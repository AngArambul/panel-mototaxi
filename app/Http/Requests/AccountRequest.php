<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => strtolower($this->input('email'))]);
        }
    }

    public function rules(): array
    {
        $account = $this->route('account');
        $uniqueEmail = Rule::unique(User::class, 'email');
        $uniquePhone = Rule::unique(User::class, 'telefono');
        if ($account instanceof User) {
            $uniqueEmail->ignore($account);
            $uniquePhone->ignore($account);
        }

        $rules = [
            'nombre' => ['required', 'string', 'max:100'],
            'email' => [$account instanceof User && $account->email === null ? 'nullable' : 'required', 'string', 'email', 'max:150', $uniqueEmail],
            'telefono' => ['nullable', 'string', 'regex:/^\+?[0-9]{10,15}$/', $uniquePhone],
        ];

        if (! $account) {
            $rules['rol'] = ['required', Rule::in(User::MANAGED_ROLES)];
            $rules['password'] = ['required', 'confirmed', 'max:72', Password::defaults()];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return ['email' => 'correo electrónico', 'telefono' => 'teléfono', 'password' => 'contraseña'];
    }
}
