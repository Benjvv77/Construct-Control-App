# ConstructControl (Laravel + PostgreSQL)

Versión Laravel del sistema de control de presupuestos, gastos e inventario de obras.
La versión PHP original sigue en la carpeta superior (`../app`, `../resources`).

## Requisitos

- PHP 8.3 o superior con `pdo_pgsql`
- Composer
- PostgreSQL

## Puesta en marcha

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Crea la base y el usuario en PostgreSQL y completa `DB_*` en `.env`:

```sql
CREATE ROLE constructcontrol LOGIN PASSWORD 'tu_clave';
CREATE DATABASE constructcontrol OWNER constructcontrol;
CREATE DATABASE constructcontrol_test OWNER constructcontrol; -- para las pruebas
```

```bash
php artisan migrate --seed   # tablas MGLPBC + tipos de gasto y costo
php artisan serve            # http://localhost:8000
```

## Traer los datos del sistema anterior

```bash
php artisan legado:importar
```

Lee `../app/database/ingsoftware.db` (solo lectura), copia usuarios, proyectos, gastos,
materiales y movimientos conservando sus IDs, pasa las contraseñas a hash y copia las
boletas a `storage/app/private/gastos`. Los registros huérfanos de proyectos borrados se omiten.
Opciones: `--sqlite=ruta`, `--recursos=ruta`, `--reemplazar` (vacía las tablas MGLPBC antes).

## Pruebas

```bash
php artisan test
```

Usan la base `constructcontrol_test` (ver `phpunit.xml`).

## Permisos

Cada usuario ve y gestiona solo los proyectos de los que es responsable; el rol
`Administrador` gestiona todos (`app/Policies/ProyectoPolicy.php`).

## Funciones (historias de usuario)

| HU | Función | Dónde |
|---|---|---|
| 01–02 | Login por correo y contraseña; el Administrador crea usuarios y asigna roles | `/login`, `/usuarios` |
| 03–06 | Solo el Administrador crea, edita y elimina proyectos; todos ven el listado con estado y avance | `/proyectos` |
| 07 | Ítems del presupuesto (descripción, tipo, cantidad, precio). El presupuesto del proyecto es su suma | Detalle del proyecto |
| 08 | Gasto presupuestado: se asocia a un ítem y exige boleta o factura | Detalle del proyecto |
| 09 | Gasto adicional (imprevisto): exige justificación | Detalle del proyecto |
| 10 | Los gastos de un supervisor quedan pendientes hasta que el Administrador los aprueba o rechaza (con motivo) | `/gastos/pendientes` |
| 11 | Comparativo por ítem: presupuestado, gastado, por aprobar, desviación y estado | Detalle del proyecto |
| 12–13 | Entradas y salidas de material; los gastos de materiales aprobados entran solos al inventario | Inventario |
| 14 | Inventario vs. presupuesto y conteo físico: detecta compras de más y pérdidas | Inventario |
| 15 | Curva S con plan registrado o estimado | Reporte del proyecto |
| 16 | Desviación presupuestaria por obra y por ítem | `/reportes/desviacion` |
| 17 | Reporte de gastos adicionales | Desde el detalle del proyecto |
| 18 | Los tres reportes se exportan a PDF | Botón "Exportar PDF" |
| 19 | Registro de cambios: quién creó, modificó, aprobó o eliminó cada dato | `/auditoria` |
| 20 | Páginas de error y mensajes de validación claros, en español | Toda la app |

**Roles:** el Administrador gestiona todo. El Supervisor ve los proyectos de los que es responsable y registra
gastos y movimientos de inventario; sus gastos quedan pendientes de aprobación.

**Alertas:** al llegar al 90 % del presupuesto (`Proyecto::UMBRAL_ALERTA`) un proyecto activo pasa solo a "Alerta".
Solo los gastos aprobados cuentan en totales, reportes, alertas e inventario.

## Recuperar contraseña

En desarrollo `MAIL_MAILER=log`: el correo con el enlace no se envía, queda escrito en
`storage/logs/laravel.log`. Para enviarlo de verdad, configura `MAIL_*` en `.env` con un servidor SMTP.

## Vistas

Las vistas son plantillas PHP simples en `resources/views` (sin Blade ni subcarpetas). Cada página
interna abre con `view('encabezado', [...])` y cierra con `view('pie')`; las de acceso usan
`encabezado_acceso` y `pie_acceso`. Para montos y cantidades están `pesos()` y `numero()` en `app/helpers.php`.
