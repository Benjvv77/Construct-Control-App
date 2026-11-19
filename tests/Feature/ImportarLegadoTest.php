<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PDO;
use Tests\TestCase;

class ImportarLegadoTest extends TestCase
{
    use RefreshDatabase;

    private string $carpeta;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();

        $this->carpeta = sys_get_temp_dir().'/legado_'.uniqid();
        mkdir($this->carpeta.'/recursos/uploads/gastos', 0755, true);
        file_put_contents($this->carpeta.'/recursos/uploads/gastos/boleta.pdf', '%PDF-1.4');

        $db = new PDO('sqlite:'.$this->carpeta.'/legado.db');
        $db->exec(<<<'SQL'
            CREATE TABLE MGLPBC_USUARIO (id_usuario INTEGER PRIMARY KEY, nombre_usuario TEXT, apellido_materno TEXT, apellido_paterno TEXT, correo_usuario TEXT, password_usuario TEXT, rol_usuario TEXT, estado_usuario INTEGER);
            CREATE TABLE MGLPBC_PROYECTO (id_proyecto INTEGER PRIMARY KEY, id_usuario INTEGER, nombre_proyecto TEXT, descripcion_proyecto TEXT, fecha_inicio_proyecto DATE, fecha_termino_proyecto DATE, estado_proyecto INTEGER, presupuesto_proyecto REAL, presupuesto_proyecto_final REAL);
            CREATE TABLE MGLPBC_GASTOS (id_gasto INTEGER PRIMARY KEY, id_proyecto INTEGER, id_tipo_gasto INTEGER, nombre_gasto TEXT, descripcion_gasto TEXT, monto_gasto REAL, fecha_gasto DATE, id_tipo_costo INTEGER, documento_gasto TEXT, cantidad_gasto REAL);
            CREATE TABLE MGLPBC_MATERIAL (id_material INTEGER PRIMARY KEY, nombre_material TEXT, unidad_material TEXT, stock_actual REAL, stock_minimo REAL, estado_material INTEGER, id_proyecto INTEGER, costo_estimado_material REAL, stock_inicial REAL, id_gasto INTEGER);
            CREATE TABLE MGLPBC_MOVIMIENTO_INVENTARIO (id_movimiento INTEGER PRIMARY KEY, id_proyecto INTEGER, id_material INTEGER, tipo_movimiento TEXT, cantidad_movimiento REAL, fecha_movimiento TEXT, descripcion_movimiento TEXT);
            CREATE TABLE MGLPBC_ITEM_PRESUPUESTO (id_item_presupuesto INTEGER PRIMARY KEY, id_proyecto INTEGER, id_material INTEGER, cantidad_estimada REAL, costo_unitario_estimado REAL);
            CREATE TABLE MGLPBC_CURVA_S (id_curva INTEGER PRIMARY KEY, id_proyecto INTEGER, fecha_evaluacion DATE, monto_planificado_acumulado REAL, monto_real_acumulado REAL);

            INSERT INTO MGLPBC_USUARIO VALUES (1, 'Ana', '', 'Pérez', 'ana@test.cl', '123456', 'Administrador', 1);
            INSERT INTO MGLPBC_PROYECTO VALUES (7, 1, 'Obra', '', '2026-01-01', '', 1, 1000000, NULL);
            INSERT INTO MGLPBC_GASTOS VALUES (21, 7, 1, 'Cemento', NULL, 50000, '2026-02-01', 1, 'uploads/gastos/boleta.pdf', '');
            INSERT INTO MGLPBC_GASTOS VALUES (3, 1, 1, 'Huérfano', NULL, 100, '2026-02-01', 1, NULL, 0);
            INSERT INTO MGLPBC_MATERIAL VALUES (10, 'Cemento', 'sacos', 8, 5, 1, 7, 50000, 10, NULL);
            INSERT INTO MGLPBC_MATERIAL VALUES (9, 'Huérfano', 'sacos', 1, 5, 1, 4, 0, 1, NULL);
            INSERT INTO MGLPBC_MOVIMIENTO_INVENTARIO VALUES (4, 7, 10, 'SALIDA', 2, '2026-02-02', '');
            INSERT INTO MGLPBC_MOVIMIENTO_INVENTARIO VALUES (1, 4, 1, 'SALIDA', 1, '2026-02-02', '');
        SQL);
    }

    protected function tearDown(): void
    {
        exec('rm -rf '.escapeshellarg($this->carpeta));

        parent::tearDown();
    }

    public function test_importa_los_datos_y_omite_los_huerfanos(): void
    {
        $this->artisan('legado:importar', [
            '--sqlite' => $this->carpeta.'/legado.db',
            '--recursos' => $this->carpeta.'/recursos',
        ])->assertSuccessful();

        $this->assertDatabaseCount('mglpbc_proyecto', 1);
        $this->assertDatabaseHas('mglpbc_proyecto', ['id_proyecto' => 7, 'fecha_termino_proyecto' => null, 'descripcion_proyecto' => null]);
        $this->assertDatabaseCount('mglpbc_gastos', 1);
        $this->assertDatabaseHas('mglpbc_gastos', ['id_gasto' => 21, 'cantidad_gasto' => 0, 'documento_gasto' => 'gastos/boleta.pdf']);
        $this->assertDatabaseCount('mglpbc_material', 1);

        // El presupuesto antiguo queda como ítem, y el gasto queda aprobado y asociado a él
        $item = \App\Models\ItemPresupuesto::sole();
        $this->assertSame('Presupuesto general', $item->descripcion_item);
        $this->assertEquals(1000000, $item->total());
        $this->assertDatabaseHas('mglpbc_gastos', ['id_gasto' => 21, 'estado_gasto' => 'aprobado', 'id_item_presupuesto' => $item->id_item_presupuesto]);

        // La salida antigua más una entrada inicial que explica el stock: 10 − 2 = 8
        $this->assertDatabaseCount('mglpbc_movimiento_inventario', 2);
        $this->assertDatabaseHas('mglpbc_movimiento_inventario', ['id_material' => 10, 'tipo_movimiento' => 'ENTRADA', 'cantidad_movimiento' => 10]);
        Storage::assertExists('gastos/boleta.pdf');

        // La clave en texto plano queda con hash y permite iniciar sesión
        $usuario = Usuario::sole();
        $this->assertTrue(password_verify('123456', $usuario->getRawOriginal('password_usuario')));
        $this->post('/login', ['email' => 'ana@test.cl', 'password' => '123456'])->assertRedirect(route('inicio'));

        // Las secuencias siguen después de los IDs importados
        $this->post('/proyectos', [
            'nombre_proyecto' => 'Nuevo',
            'fecha_inicio_proyecto' => '2026-03-01',
            'estado_proyecto' => 1,
            'presupuesto_proyecto' => 1000,
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('mglpbc_proyecto', ['id_proyecto' => 8, 'nombre_proyecto' => 'Nuevo']);
    }

    public function test_no_importa_dos_veces_sin_reemplazar(): void
    {
        $opciones = ['--sqlite' => $this->carpeta.'/legado.db', '--recursos' => $this->carpeta.'/recursos'];

        $this->artisan('legado:importar', $opciones)->assertSuccessful();
        $this->artisan('legado:importar', $opciones)->assertFailed();
        $this->artisan('legado:importar', [...$opciones, '--reemplazar' => true])->assertSuccessful();

        $this->assertDatabaseCount('mglpbc_proyecto', 1);
    }
}
