<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DispatchClient
{
    public function request(string $method, string $path, ?string $token, array $body = []): ?Response
    {
        $client = Http::acceptJson()->withoutRedirecting()->connectTimeout(3)->timeout(15);
        if ($token !== null) {
            $client = $client->withToken($token);
        }

        try {
            $response = $client->send($method, rtrim(config('services.dispatch.url'), '/').$path,
                $method === 'GET' ? [] : ['json' => $body]);
        } catch (ConnectionException) {
            Log::warning('Despacho no disponible', ['operacion' => $path]);
            return null;
        }

        if ($response->serverError()) {
            Log::warning('Despacho rechazó la operación', ['operacion' => $path, 'http' => $response->status()]);
        }

        return $response;
    }
}
