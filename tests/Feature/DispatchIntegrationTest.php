<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DispatchIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_forwards_exact_body_and_signature(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);
        $raw = '{ "object": "whatsapp_business_account", "entry": [] }';
        $this->call('POST', '/api/webhook/whatsapp', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_HUB_SIGNATURE_256' => 'sha256=test'], $raw)->assertOk();
        Http::assertSent(fn ($request) => $request->body() === $raw && $request->hasHeader('X-Hub-Signature-256', 'sha256=test'));
    }

    public function test_webhook_failure_is_not_acknowledged_as_success(): void
    {
        Http::fake(['*' => Http::response(['ok' => false], 503)]);
        $this->postJson('/api/webhook/whatsapp', ['entry' => []])->assertStatus(503);
    }

    public function test_verification_preserves_meta_query_and_challenge(): void
    {
        Http::fake(['*' => Http::response('challenge-test', 200)]);
        $this->get('/api/webhook/whatsapp?hub.mode=subscribe&hub.verify_token=test&hub.challenge=challenge-test')->assertOk()->assertSee('challenge-test');
        Http::assertSent(fn ($request) => $request['hub.mode'] === 'subscribe' && $request['hub.verify_token'] === 'test');
    }

    public function test_web_account_alone_does_not_grant_dispatch_privileges(): void
    {
        $this->actingAs(User::factory()->create())->getJson('/dispatch/data')->assertUnauthorized();
        Http::fake(['*' => Http::response(['token' => 'driver-token', 'usuario' => ['rol' => 'CHOFER']], 200)]);
        $this->postJson('/dispatch/login', ['telefono' => '3331231234', 'password' => 'password123'])->assertForbidden();
        $this->assertNull(session('dispatch_token'));
    }

    public function test_dispatch_token_stays_on_server_and_scoped_data_is_proxied(): void
    {
        Http::fake([
            '*/api/auth/login' => Http::response(['token' => 'private-token', 'usuario' => ['rol' => 'PROPIETARIO']], 200),
            '*/api/unidades' => Http::response(['ok' => true, 'unidades' => [], 'viajes' => []], 200),
        ]);
        $this->actingAs(User::factory()->create())->postJson('/dispatch/login', ['telefono' => '3331231234', 'password' => 'password123'])
            ->assertOk()->assertDontSee('private-token');
        $this->assertSame('private-token', Crypt::decryptString(session('dispatch_token')));
        $this->getJson('/dispatch/data')->assertOk();
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/api/unidades') && $request->hasHeader('Authorization', 'Bearer private-token'));
    }

    public function test_public_registration_cannot_create_an_administrator(): void
    {
        $this->post('/register', ['name' => 'New person', 'email' => 'new@example.test', 'password' => 'password123', 'password_confirmation' => 'password123', 'rol' => 'ADMINISTRADOR'])->assertRedirect('/dashboard');
        $this->assertSame('PASAJERO', User::where('email', 'new@example.test')->firstOrFail()->rol);
    }

    public function test_trip_form_redirects_and_replayed_submission_is_rejected(): void
    {
        Http::fake(['*/api/viajes' => Http::response(['ok' => true, 'msg' => 'Viaje registrado.'])]);
        $this->actingAs(User::factory()->create())->withSession([
            'dispatch_token' => Crypt::encryptString('synthetic-owner'),
            'dispatch_forms.trip' => 'single-use',
        ]);
        $body = ['origen' => 'Origen sintético', 'destino' => 'Destino sintético', 'numeroUnidad' => '01', '_submission' => 'single-use', 'owner_id' => 'unauthorized'];
        $this->post(route('dispatch.trips.store'), $body)->assertRedirect(route('dashboard'))->assertSessionHas('status', 'Viaje registrado.');
        $this->post(route('dispatch.trips.store'), $body)->assertRedirect(route('dashboard'))->assertSessionHas('dispatch_error');
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->data() === ['origen' => 'Origen sintético', 'destino' => 'Destino sintético', 'numeroUnidad' => '01']);
    }

    public function test_invalid_web_and_json_payloads_do_not_reach_dispatch(): void
    {
        Http::fake();
        $this->actingAs(User::factory()->create());
        $this->from(route('dashboard'))->post(route('dispatch.trips.store'), ['origen' => '', 'numeroUnidad' => 'x'])
            ->assertRedirect(route('dashboard'))->assertSessionHasErrors(['origen', 'destino', 'numeroUnidad']);
        $this->postJson(route('dispatch.invitations.store'), ['numeroUnidad' => '12345'])->assertUnprocessable()->assertJsonValidationErrors('numeroUnidad');
        Http::assertNothingSent();
    }

    public function test_invitation_keeps_node_permission_check_and_connection_failure(): void
    {
        $this->actingAs(User::factory()->create())->withSession(['dispatch_token' => Crypt::encryptString('synthetic-owner')]);
        Http::fake(['*/api/unidades/invitacion' => Http::sequence()->push(['ok' => false, 'msg' => 'Unidad ajena.'], 403)->push(['ok' => false], 503)]);
        $this->postJson(route('dispatch.invitations.store'), ['numeroUnidad' => '02'])->assertForbidden();
        $this->postJson(route('dispatch.invitations.store'), ['numeroUnidad' => '02'])->assertStatus(503)->assertJson(['ok' => false]);
    }
}
