<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PanelController extends Controller
{
    public function show(Request $request): View
    {
        $nonces = [];
        foreach (['trip', 'invitation'] as $form) {
            $key = 'dispatch_forms.'.$form;
            $nonce = $request->session()->get($key) ?? Str::random(32);
            $request->session()->put($key, $nonce);
            $nonces[$form] = $nonce;
        }

        return view('dashboard', ['nonces' => $nonces]);
    }
}
