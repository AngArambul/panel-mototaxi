<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DemonstrationSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing']) || DB::connection()->getDriverName() !== 'sqlite'
            || basename(DB::connection()->getDatabaseName()) !== 'mototaxi-demostracion.sqlite') {
            throw new RuntimeException('La demostración requiere el archivo SQLite mototaxi-demostracion.sqlite y APP_ENV=local o testing.');
        }

        $password = $this->command?->secret('Contraseña para las cuentas sintéticas (12 a 72 bytes)');
        if (! is_string($password) || strlen($password) < 12 || strlen($password) > 72) {
            throw new RuntimeException('La contraseña no tiene la longitud requerida.');
        }

        $accounts = [
            ['admin@mototaxi.test', 'Administración de prueba', User::ADMINISTRATOR],
            ['conductor@mototaxi.test', 'Conductor de prueba', User::DRIVER],
            ['pasajero@mototaxi.test', 'Pasajero de prueba', User::PASSENGER],
        ];

        DB::transaction(function () use ($accounts, $password) {
            foreach ($accounts as [$email, $name, $role]) {
                if (User::query()->where('email', $email)->exists()) {
                    continue;
                }

                $account = new User(['nombre' => $name, 'email' => $email, 'password' => Hash::make($password)]);
                $account->rol = $role;
                $account->email_verified_at = now();
                $account->save();
            }
        });
    }
}
