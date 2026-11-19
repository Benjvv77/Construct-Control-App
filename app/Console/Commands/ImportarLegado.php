<?php

namespace App\Console\Commands;

use Database\Seeders\CatalogoSeeder;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Copia los datos del sistema PHP original (SQLite) a PostgreSQL.
 *
 * Conserva los IDs y omite los registros huérfanos que dejaron
 * proyectos borrados en la versión anterior.
 */
class ImportarLegado extends Command
{
    protected $signature = 'legado:importar
        {--sqlite= : Ruta a la base SQLite original (por defecto ../app/database/ingsoftware.db)}
        {--recursos= : Carpeta resources/ original donde están los uploads (por defecto ../resources)}
        {--reemplazar : Vacía las tablas MGLPBC antes de importar}';

    protected $description = 'Importa usuarios, proyectos, gastos e inventario desde la base SQLite del sistema anterior';

    private const TABLAS = [
        'mglpbc_auditoria',
        'mglpbc_movimiento_inventario',
        'mglpbc_item_presupuesto',
        'mglpbc_material',
        'mglpbc_curva_s',
        'mglpbc_gastos',
        'mglpbc_proyecto',
        'mglpbc_usuario',
    ];

    public function handle(): int
    {
        $rutaSqlite = $this->option('sqlite') ?: base_path('../app/database/ingsoftware.db');
        $rutaRecursos = rtrim($this->option('recursos') ?: base_path('../resources'), '/');

        if (! is_file($rutaSqlite)) {
            $this->error("No existe la base SQLite: $rutaSqlite");

            return self::FAILURE;
        }

        config(['database.connections.legado' => [
            'driver' => 'sqlite',
            'database' => $rutaSqlite,
            'foreign_key_constraints' => false,
        ]]);

        $legado = DB::connection('legado');

        if (DB::table('mglpbc_usuario')->exists() && ! $this->option('reemplazar')) {
            $this->error('PostgreSQL ya tiene datos. Usa --reemplazar para vaciar las tablas MGLPBC e importar de nuevo.');

            return self::FAILURE;
        }

        $documentosCopiados = [];

        try {
            DB::transaction(function () use ($legado, $rutaRecursos, &$documentosCopiados) {
                if ($this->option('reemplazar')) {
                    DB::statement('TRUNCATE '.implode(', ', self::TABLAS).' RESTART IDENTITY CASCADE');
                }

                (new CatalogoSeeder)->run();

                $this->importarUsuarios($legado);
                $proyectos = $this->importarProyectos($legado);
                $items = $this->crearPresupuestoGeneral();
                $gastos = $this->importarGastos($legado, $proyectos, $items, $rutaRecursos, $documentosCopiados);
                $materiales = $this->importarMateriales($legado, $proyectos, $gastos);
                $this->importarDependientesDeMaterial($legado, 'mglpbc_movimiento_inventario', $proyectos, $materiales);
                $this->registrarEntradasIniciales();
                $this->importarPorProyecto($legado, 'mglpbc_curva_s', $proyectos);

                foreach ($this->clavesPrimarias() as $tabla => $clave) {
                    DB::statement("SELECT setval(pg_get_serial_sequence('$tabla', '$clave'), COALESCE((SELECT MAX($clave) FROM $tabla), 1), (SELECT MAX($clave) FROM $tabla) IS NOT NULL)");
                }
            });
        } catch (Throwable $e) {
            Storage::delete($documentosCopiados);

            throw $e;
        }

        $this->newLine();
        $this->info('Importación completa.');

        return self::SUCCESS;
    }

    private function importarUsuarios(Connection $legado): void
    {
        $usuarios = $legado->table('MGLPBC_USUARIO')->get()->map(function ($u) {
            $password = $u->password_usuario;

            // El sistema anterior guardaba claves en texto plano; aquí se guardan con hash
            if (password_get_info($password)['algoName'] === 'unknown') {
                $password = Hash::make($password);
            }

            return [
                'id_usuario' => $u->id_usuario,
                'nombre_usuario' => $u->nombre_usuario,
                'apellido_paterno' => $u->apellido_paterno,
                'apellido_materno' => $this->nulo($u->apellido_materno),
                'correo_usuario' => $u->correo_usuario,
                'password_usuario' => $password,
                'rol_usuario' => $u->rol_usuario,
                'estado_usuario' => $u->estado_usuario,
            ];
        });

        DB::table('mglpbc_usuario')->insert($usuarios->all());
        $this->reportar('Usuarios', $usuarios->count(), 0);
    }

    /** @return array<int, true> IDs de proyectos importados */
    private function importarProyectos(Connection $legado): array
    {
        $usuarios = DB::table('mglpbc_usuario')->pluck('id_usuario')->flip();
        $todos = $legado->table('MGLPBC_PROYECTO')->get();

        $validos = $todos->filter(fn ($p) => $usuarios->has($p->id_usuario))->map(fn ($p) => [
            'id_proyecto' => $p->id_proyecto,
            'id_usuario' => $p->id_usuario,
            'nombre_proyecto' => $p->nombre_proyecto,
            'descripcion_proyecto' => $this->nulo($p->descripcion_proyecto),
            'fecha_inicio_proyecto' => $p->fecha_inicio_proyecto,
            'fecha_termino_proyecto' => $this->nulo($p->fecha_termino_proyecto),
            'estado_proyecto' => $p->estado_proyecto,
            'presupuesto_proyecto' => $p->presupuesto_proyecto,
        ]);

        DB::table('mglpbc_proyecto')->insert($validos->values()->all());
        $this->reportar('Proyectos', $validos->count(), $todos->count() - $validos->count());

        return $validos->pluck('id_proyecto')->flip()->map(fn () => true)->all();
    }

    /** @return array<int, true> IDs de gastos importados */
    /**
     * El sistema anterior tenía un solo monto de presupuesto: pasa a ser el ítem "Presupuesto general".
     *
     * @return array<int, int> id_proyecto => id_item_presupuesto
     */
    private function crearPresupuestoGeneral(): array
    {
        $otros = DB::table('mglpbc_tipo_gastos')->where('nombre_tipo_gasto', 'Otros')->value('id_tipo_gasto');
        $items = [];

        foreach (DB::table('mglpbc_proyecto')->where('presupuesto_proyecto', '>', 0)->get() as $proyecto) {
            $items[$proyecto->id_proyecto] = DB::table('mglpbc_item_presupuesto')->insertGetId([
                'id_proyecto' => $proyecto->id_proyecto,
                'id_tipo_gasto' => $otros,
                'descripcion_item' => 'Presupuesto general',
                'cantidad' => 1,
                'unidad_item' => 'global',
                'precio_unitario' => $proyecto->presupuesto_proyecto,
            ], 'id_item_presupuesto');
        }

        return $items;
    }

    private function importarGastos(Connection $legado, array $proyectos, array $items, string $rutaRecursos, array &$documentosCopiados): array
    {
        $todos = $legado->table('MGLPBC_GASTOS')->get();
        $sinDocumento = 0;

        $responsables = DB::table('mglpbc_proyecto')->pluck('id_usuario', 'id_proyecto');

        $validos = $todos->filter(fn ($g) => isset($proyectos[$g->id_proyecto]))->map(function ($g) use ($rutaRecursos, $items, $responsables, &$documentosCopiados, &$sinDocumento) {
            $documento = null;

            if ($this->nulo($g->documento_gasto)) {
                $origen = $rutaRecursos.'/'.$g->documento_gasto;

                if (is_file($origen)) {
                    $documento = 'gastos/'.basename($origen);
                    Storage::put($documento, file_get_contents($origen));
                    $documentosCopiados[] = $documento;
                } else {
                    $sinDocumento++;
                }
            }

            return [
                'id_gasto' => $g->id_gasto,
                'id_proyecto' => $g->id_proyecto,
                'id_tipo_gasto' => $g->id_tipo_gasto,
                'id_tipo_costo' => $this->nulo($g->id_tipo_costo),
                'nombre_gasto' => $g->nombre_gasto,
                'descripcion_gasto' => $this->nulo($g->descripcion_gasto),
                'monto_gasto' => $g->monto_gasto,
                'fecha_gasto' => $g->fecha_gasto,
                'documento_gasto' => $documento,
                'cantidad_gasto' => $this->nulo($g->cantidad_gasto) ?? 0,
                // Los gastos del sistema anterior ya estaban vigentes: quedan aprobados por el responsable
                'estado_gasto' => 'aprobado',
                'id_usuario_registro' => $responsables[$g->id_proyecto],
                'id_usuario_revisor' => $responsables[$g->id_proyecto],
                'id_item_presupuesto' => (int) $this->nulo($g->id_tipo_costo) === 2 ? null : ($items[$g->id_proyecto] ?? null),
            ];
        });

        DB::table('mglpbc_gastos')->insert($validos->values()->all());
        $this->reportar('Gastos', $validos->count(), $todos->count() - $validos->count());
        $this->line('    Documentos copiados: '.count($documentosCopiados).($sinDocumento ? ", no encontrados: $sinDocumento" : ''));

        return $validos->pluck('id_gasto')->flip()->map(fn () => true)->all();
    }

    /** @return array<int, true> IDs de materiales importados */
    private function importarMateriales(Connection $legado, array $proyectos, array $gastos): array
    {
        $todos = $legado->table('MGLPBC_MATERIAL')->get();

        $validos = $todos->filter(fn ($m) => isset($proyectos[$m->id_proyecto]))->map(fn ($m) => [
            'id_material' => $m->id_material,
            'id_proyecto' => $m->id_proyecto,
            'id_gasto' => isset($gastos[$m->id_gasto]) ? $m->id_gasto : null,
            'nombre_material' => $m->nombre_material,
            'unidad_material' => $m->unidad_material,
            'stock_actual' => $this->nulo($m->stock_actual) ?? 0,
            'stock_inicial' => $this->nulo($m->stock_inicial) ?? 0,
            'stock_minimo' => $this->nulo($m->stock_minimo) ?? 0,
            'costo_estimado_material' => $this->nulo($m->costo_estimado_material) ?? 0,
            'estado_material' => $m->estado_material,
        ]);

        DB::table('mglpbc_material')->insert($validos->values()->all());
        $this->reportar('Materiales', $validos->count(), $todos->count() - $validos->count());

        return $validos->pluck('id_material')->flip()->map(fn () => true)->all();
    }

    private function importarDependientesDeMaterial(Connection $legado, string $tabla, array $proyectos, array $materiales): void
    {
        $todos = $legado->table(strtoupper($tabla))->get();

        $validos = $todos
            ->filter(fn ($fila) => isset($proyectos[$fila->id_proyecto], $materiales[$fila->id_material]))
            ->map(fn ($fila) => array_map(fn ($valor) => $this->nulo($valor), (array) $fila));

        DB::table($tabla)->insert($validos->values()->all());
        $this->reportar(str_replace('mglpbc_', '', $tabla), $validos->count(), $todos->count() - $validos->count());
    }

    /**
     * El sistema anterior sumaba stock sin registrar movimientos: la diferencia queda como una entrada
     * inicial, para que siempre se cumpla  stock = entradas − salidas + ajustes.
     */
    private function registrarEntradasIniciales(): void
    {
        $materiales = DB::select("
            SELECT m.id_material, m.id_proyecto, m.stock_actual,
                   COALESCE(SUM(CASE mi.tipo_movimiento WHEN 'ENTRADA' THEN mi.cantidad_movimiento
                                                        WHEN 'SALIDA' THEN -mi.cantidad_movimiento
                                                        ELSE mi.cantidad_movimiento END), 0) AS explicado,
                   COALESCE((SELECT fecha_gasto FROM mglpbc_gastos g WHERE g.id_gasto = m.id_gasto),
                            (SELECT fecha_inicio_proyecto FROM mglpbc_proyecto p WHERE p.id_proyecto = m.id_proyecto)) AS fecha
            FROM mglpbc_material m
            LEFT JOIN mglpbc_movimiento_inventario mi ON mi.id_material = m.id_material
            GROUP BY m.id_material
        ");

        foreach ($materiales as $material) {
            $faltante = (float) $material->stock_actual - (float) $material->explicado;

            if ($faltante > 0) {
                DB::table('mglpbc_movimiento_inventario')->insert([
                    'id_proyecto' => $material->id_proyecto,
                    'id_material' => $material->id_material,
                    'tipo_movimiento' => 'ENTRADA',
                    'cantidad_movimiento' => $faltante,
                    'fecha_movimiento' => $material->fecha,
                    'descripcion_movimiento' => 'Saldo inicial (ingresado antes del historial de movimientos)',
                ]);
            }
        }
    }

    private function importarPorProyecto(Connection $legado, string $tabla, array $proyectos): void
    {
        $todos = $legado->table(strtoupper($tabla))->get();
        $validos = $todos->filter(fn ($fila) => isset($proyectos[$fila->id_proyecto]))->map(fn ($fila) => array_map(fn ($valor) => $this->nulo($valor), (array) $fila));

        DB::table($tabla)->insert($validos->values()->all());
        $this->reportar(str_replace('mglpbc_', '', $tabla), $validos->count(), $todos->count() - $validos->count());
    }

    private function clavesPrimarias(): array
    {
        return [
            'mglpbc_usuario' => 'id_usuario',
            'mglpbc_proyecto' => 'id_proyecto',
            'mglpbc_gastos' => 'id_gasto',
            'mglpbc_material' => 'id_material',
            'mglpbc_movimiento_inventario' => 'id_movimiento',
            'mglpbc_item_presupuesto' => 'id_item_presupuesto',
            'mglpbc_curva_s' => 'id_curva',
        ];
    }

    private function nulo(mixed $valor): mixed
    {
        return $valor === '' ? null : $valor;
    }

    private function reportar(string $nombre, int $importados, int $omitidos): void
    {
        $this->line(sprintf('  %-26s %3d importados%s', $nombre, $importados, $omitidos ? ", $omitidos huérfanos omitidos" : ''));
    }
}
