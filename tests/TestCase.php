<?php

namespace Tests;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function administrador(): Usuario
    {
        return Usuario::factory()->administrador()->create();
    }

    /** Boleta de prueba para los gastos presupuestados, que la exigen (HU08). */
    protected function boleta(): \Illuminate\Http\UploadedFile
    {
        return \Illuminate\Http\UploadedFile::fake()->create('boleta.pdf', 20, 'application/pdf');
    }
}
