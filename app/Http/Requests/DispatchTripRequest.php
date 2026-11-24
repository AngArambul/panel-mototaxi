<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DispatchTripRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'origen' => ['required', 'string', 'max:300'],
            'destino' => ['required', 'string', 'max:300'],
            'numeroUnidad' => ['required', 'regex:/^[0-9]{1,4}$/D'],
            'pasajero' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function attributes(): array
    {
        return ['numeroUnidad' => 'unidad'];
    }
}
