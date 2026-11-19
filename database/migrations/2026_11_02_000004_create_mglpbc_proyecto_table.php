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
        Schema::create('mglpbc_proyecto', function (Blueprint $table) {
            $table->id('id_proyecto');
            $table->foreignId('id_usuario')->constrained('mglpbc_usuario', 'id_usuario');
            $table->string('nombre_proyecto');
            $table->text('descripcion_proyecto')->nullable();
            $table->date('fecha_inicio_proyecto');
            $table->date('fecha_termino_proyecto')->nullable();
            $table->smallInteger('estado_proyecto')->default(1);
            $table->decimal('presupuesto_proyecto', 15, 2);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mglpbc_proyecto');
    }
};
