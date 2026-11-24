<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('usuarios')) {
            Schema::create('usuarios', function (Blueprint $table) {
                $table->uuid('id_usuario')->primary();
                $table->string('nombre');
                $table->string('telefono')->nullable()->unique();
                $table->string('email')->nullable()->unique();
                $table->string('password')->nullable();
                $table->string('rol')->default('PASAJERO');
                $table->boolean('activo')->default(true);
                $table->rememberToken();
                $table->timestamp('email_verified_at')->nullable();
                $table->timestamp('creado_en')->useCurrent();
                $table->timestamp('actualizado_en')->useCurrent();
            });
        } else {
            Schema::table('usuarios', function (Blueprint $table) {
                $table->string('telefono')->nullable()->change();
            });
            if (! Schema::hasColumn('usuarios', 'email_verified_at')) {
                Schema::table('usuarios', fn (Blueprint $table) => $table->timestamp('email_verified_at')->nullable());
            }
        }
        if (Schema::hasTable('sessions')) {
            Schema::table('sessions', fn (Blueprint $table) => $table->string('user_id')->nullable()->change());
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive: usuarios may predate Laravel and own transport records.
    }
};
