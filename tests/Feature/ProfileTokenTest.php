<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class ProfileTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_has_limited_scope_expiry_and_can_be_revoked_only_by_its_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($owner)->post(route('profile.tokens.store'), ['nombre' => 'Prueba', 'password' => 'password'])
            ->assertRedirect(route('profile.tokens.index'))->assertSessionHas('issued_token');
        $plain = Crypt::decryptString(session('issued_token'));
        $this->assertNotSame($plain, session('issued_token'));
        $record = PersonalAccessToken::findToken($plain);
        $this->assertSame(['perfil:leer'], $record->abilities);
        $this->assertSame($owner->getKey(), $record->tokenable_id);
        $this->assertTrue($record->expires_at->between(now()->addDays(29), now()->addDays(30)->addMinute()));
        $this->assertNotSame($plain, $record->token);
        $this->get(route('profile.tokens.index'))->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertSee($plain);
        $this->get(route('profile.tokens.index'))->assertOk()->assertDontSee($plain);

        $this->actingAs($other)->delete(route('profile.tokens.destroy', $record))->assertNotFound();
        $this->actingAs($owner)->delete(route('profile.tokens.destroy', $record))->assertRedirect(route('profile.tokens.index'));
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $record->id]);
        Auth::forgetGuards();
        $this->getJson('/api/user', ['Authorization' => 'Bearer '.$plain])->assertUnauthorized();
    }

    public function test_profile_requires_an_active_user_with_an_unexpired_scoped_token(): void
    {
        $user = User::factory()->create();
        $this->getJson('/api/user')->assertUnauthorized();
        $wrong = $user->createToken('no-perfil', ['otro'])->plainTextToken;
        $expired = $user->createToken('vencido', ['perfil:leer'], now()->subSecond())->plainTextToken;
        $current = $user->createToken('vigente', ['perfil:leer'])->plainTextToken;

        $this->getJson('/api/user', ['Authorization' => 'Bearer '.$wrong])->assertForbidden();
        Auth::forgetGuards();
        $this->getJson('/api/user', ['Authorization' => 'Bearer '.$expired])->assertUnauthorized();
        Auth::forgetGuards();
        $this->getJson('/api/user', ['Authorization' => 'Bearer '.$current])->assertOk()
            ->assertExactJson(['id_usuario' => $user->getKey(), 'nombre' => $user->nombre, 'rol' => $user->rol]);

        $user->forceFill(['activo' => false])->save();
        Auth::forgetGuards();
        $this->getJson('/api/user', ['Authorization' => 'Bearer '.$current])->assertForbidden();
    }

    public function test_wrong_password_and_missing_token_name_do_not_create_a_token(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->from(route('profile.tokens.index'))->post(route('profile.tokens.store'), ['password' => 'incorrecta'])
            ->assertRedirect(route('profile.tokens.index'))->assertSessionHasErrors(['nombre', 'password']);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
