<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccountAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_reads_updates_deactivates_and_reactivates_an_account(): void
    {
        $admin = User::factory()->create(['rol' => User::ADMINISTRATOR]);
        $this->actingAs($admin)->get(route('accounts.create'))->assertOk();

        $password = 'sintetica-segura-para-pruebas';
        $this->post(route('accounts.store'), [
            'nombre' => 'Conductor sintético', 'email' => 'Conductor@mototaxi.test',
            'telefono' => '5550102300', 'rol' => User::DRIVER,
            'password' => $password, 'password_confirmation' => $password,
            'id_usuario' => $admin->getKey(), 'activo' => false,
        ])->assertSessionHasNoErrors()->assertRedirect(route('accounts.index'));

        $account = User::where('email', 'conductor@mototaxi.test')->firstOrFail();
        $this->assertNotSame($admin->getKey(), $account->getKey());
        $this->assertNotSame($password, $account->password);
        $this->assertTrue(Hash::check($password, $account->password));
        $this->assertSame(User::DRIVER, $account->rol);
        $this->assertTrue($account->isActive());
        $this->get(route('accounts.index'))->assertOk()->assertSee('Conductor sintético')->assertDontSee($account->password);

        $this->get(route('accounts.edit', $account))->assertOk();
        $this->put(route('accounts.update', $account), [
            'nombre' => 'Conductor actualizado', 'email' => $account->email, 'telefono' => $account->telefono,
            'rol' => User::ADMINISTRATOR, 'password' => 'manipulado', 'activo' => false,
        ])->assertRedirect(route('accounts.index'));
        $this->assertSame('Conductor actualizado', $account->refresh()->nombre);
        $this->assertSame(User::DRIVER, $account->rol);
        $this->assertTrue($account->isActive());
        $this->assertTrue(Hash::check($password, $account->password));

        $account->createToken('pruebas');
        $resetToken = Password::createToken($account);
        $this->delete(route('accounts.destroy', $account))->assertRedirect(route('accounts.index'));
        $this->assertFalse($account->refresh()->isActive());
        $this->assertCount(0, $account->tokens);
        $this->assertFalse(Password::tokenExists($account, $resetToken));
        $this->get(route('accounts.index'))->assertSee('Desactivado');

        Auth::forgetGuards();
        $this->actingAs($account)->get(route('dashboard'))->assertForbidden();
        Auth::forgetGuards();
        $this->actingAs($admin)->patch(route('accounts.activate', $account))->assertRedirect(route('accounts.index'));
        $this->assertTrue($account->refresh()->isActive());
        Auth::forgetGuards();
        $this->actingAs($account)->get(route('dashboard'))->assertRedirect(route('login'));
        Auth::forgetGuards();
        $this->post(route('login.store'), ['email' => $account->email, 'password' => $password])->assertRedirect(route('dashboard', absolute: false));
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_accounts_routes_enforce_authentication_and_target_permissions(): void
    {
        $admin = User::factory()->create(['rol' => User::ADMINISTRATOR]);
        $otherAdmin = User::factory()->create(['rol' => User::ADMINISTRATOR]);
        $driver = User::factory()->create(['rol' => User::DRIVER]);
        $passenger = User::factory()->create(['rol' => User::PASSENGER]);

        $this->get(route('accounts.index'))->assertRedirect(route('login'));
        $this->actingAs($driver)->get(route('accounts.index'))->assertForbidden();
        $this->actingAs($passenger)->put(route('accounts.update', $driver), [
            'nombre' => 'Intento de modificar otra cuenta', 'email' => $driver->email,
        ])->assertForbidden();

        $this->actingAs($admin)->get(route('accounts.edit', ['account' => Str::uuid()]))->assertNotFound();
        $this->get(route('accounts.edit', ['account' => 'no-es-uuid']))->assertNotFound();
        $this->delete(route('accounts.destroy', $otherAdmin))->assertForbidden();
        $this->delete(route('accounts.destroy', $admin))->assertForbidden();
        $this->delete(route('profile.destroy'), ['password' => 'password'])->assertForbidden();
        $this->assertTrue($otherAdmin->refresh()->isActive());
    }

    public function test_account_form_validates_fields_role_and_duplicates_only_once(): void
    {
        $admin = User::factory()->create(['rol' => User::ADMINISTRATOR]);
        $this->actingAs($admin)->from(route('accounts.create'))->post(route('accounts.store'), [
            'nombre' => '', 'email' => 'correo-invalido', 'telefono' => '100', 'rol' => User::ADMINISTRATOR,
            'password' => 'sintetica-no-mostrar', 'password_confirmation' => 'no-coincide',
        ])->assertRedirect(route('accounts.create'))->assertSessionHasErrors(['nombre', 'email', 'telefono', 'rol', 'password']);
        $this->assertNull(session()->getOldInput('password'));
        $this->get(route('accounts.create'))->assertSee('correo-invalido')->assertDontSee('sintetica-no-mostrar');

        $payload = [
            'nombre' => 'Usuario sintético', 'email' => 'pasajero@mototaxi.test', 'telefono' => '5550102300',
            'rol' => User::PASSENGER, 'password' => 'solo-para-pruebas', 'password_confirmation' => 'solo-para-pruebas',
        ];
        $this->post(route('accounts.store'), $payload)->assertRedirect(route('accounts.index'));
        $this->post(route('accounts.store'), $payload)->assertSessionHasErrors(['email', 'telefono']);
        $this->assertDatabaseCount('usuarios', 2);
    }

    public function test_inactive_account_cannot_login_or_receive_a_password_reset(): void
    {
        Notification::fake();
        $account = User::factory()->create(['activo' => false]);
        $this->post(route('login.store'), ['email' => $account->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post(route('password.email'), ['email' => $account->email])->assertSessionHas('status');
        Notification::assertNotSentTo($account, ResetPassword::class);
    }

    public function test_expired_reset_link_leaves_password_unchanged(): void
    {
        $account = User::factory()->create();
        $token = Password::createToken($account);
        $this->travel(61)->minutes();
        $this->post(route('password.store'), [
            'email' => $account->email, 'token' => $token, 'password' => 'sintetica-distinta',
            'password_confirmation' => 'sintetica-distinta',
        ])->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('password', $account->refresh()->password));
    }

    public function test_csrf_remains_enabled_on_web_account_forms(): void
    {
        $this->app->instance('env', 'local');
        $admin = User::factory()->create(['rol' => User::ADMINISTRATOR]);
        $this->actingAs($admin)->post(route('accounts.store'), [])->assertStatus(419);
        $this->assertDatabaseCount('usuarios', 1);
    }
}
