<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;

/** Compatibility endpoint. Node owns verification, persistence and the only bot. */
class ChatbotController extends Controller
{
    public function verifyWebhook(Request $request)
    {
        try {
            $response = Http::withoutRedirecting()->connectTimeout(2)->timeout(5)->get($this->url(), [
                'hub.mode' => $request->query('hub_mode', $request->query('hub.mode')),
                'hub.verify_token' => $request->query('hub_verify_token', $request->query('hub.verify_token')),
                'hub.challenge' => $request->query('hub_challenge', $request->query('hub.challenge')),
            ]);
            return response($response->body(), $response->status())->header('Content-Type', 'text/plain');
        } catch (ConnectionException) {
            return $this->unavailable('verify');
        }
    }
    public function webhook(Request $request)
    {
        if (strlen($request->getContent()) > 262144) return response('', 413);
        try {
            $response = Http::withHeaders(['X-Hub-Signature-256' => $request->header('X-Hub-Signature-256', '')])
                ->withBody($request->getContent(), 'application/json')->withoutRedirecting()->connectTimeout(2)->timeout(5)->post($this->url());
            return response($response->body(), $response->status())->header('Content-Type', 'application/json');
        } catch (ConnectionException) {
            return $this->unavailable('receive');
        }
    }

    private function unavailable(string $operation)
    {
        Log::warning('Webhook de despacho no disponible', ['operacion' => $operation]);

        return response()->json(['ok' => false, 'msg' => 'Servidor de despacho inaccesible.'], 503);
    }

    private function url(): string
    {
        return rtrim(config('services.dispatch.url'), '/').'/api/webhook/whatsapp';
    }
}
