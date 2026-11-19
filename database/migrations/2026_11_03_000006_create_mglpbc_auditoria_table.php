<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HU19: registro de quién creó, modificó o eliminó cada dato, y cuándo.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mglpbc_auditoria', function (Blueprint $table) {
            $table->id('id_auditoria');
            $table->foreignId('id_usuario')->nullable()->constrained('mglpbc_usuario', 'id_usuario')->nullOnDelete();
            $table->string('accion', 20);
            $table->string('entidad', 50);
            $table->unsignedBigInteger('id_registro');
            $table->foreignId('id_proyecto')->nullable()->index();
            $table->string('descripcion');
            $table->jsonb('cambios')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('fecha')->useCurrent()->index();
            $table->index(['entidad', 'id_registro']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mglpbc_auditoria');
    }
};
