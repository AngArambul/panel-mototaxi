<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DispatchInvitationRequest extends FormRequest
{
    protected $errorBag = 'invitation';

    public function rules(): array
    {
        return ['numeroUnidad' => ['required', 'regex:/^[0-9]{1,4}$/D']];
    }

    public function attributes(): array
    {
        return ['numeroUnidad' => 'unidad'];
    }
}
