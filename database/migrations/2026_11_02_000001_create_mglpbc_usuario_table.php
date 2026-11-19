<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mglpbc_usuario', function (Blueprint $table) {
            $table->id('id_usuario');
            $table->string('nombre_usuario', 100);
            $table->string('apellido_paterno', 100);
            $table->string('apellido_materno', 100)->nullable();
            $table->string('correo_usuario')->unique();
            $table->string('password_usuario');
            $table->string('rol_usuario', 50);
            $table->smallInteger('estado_usuario')->default(1);
            $table->rememberToken();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mglpbc_usuario');
    }
};
