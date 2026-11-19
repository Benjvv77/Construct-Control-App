<?= view('encabezado', ['titulo' => 'ConstructControl — Usuarios', 'estilos' => ['proyectos']]) ?>

<main class="main">

    <section class="hero">
        <div class="hero-content">
            <span class="eyebrow">Administración</span>
            <h1>Usuarios</h1>
            <p>
                Crea cuentas, asigna roles y desactiva el acceso de quienes ya no participan en las obras.
            </p>
        </div>

        <button class="btn-hero" onclick="abrirUsuario()">
            + Nuevo usuario
        </button>
    </section>

    <?= view('alertas') ?>

    <section class="projects-card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Correo</th>
                        <th>Rol</th>
                        <th>Proyectos</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td>
                            <div class="project-info">
                                <div class="project-icon">👷</div>
                                <div>
                                    <strong><?= e($u->nombreCompleto()) ?></strong>
                                    <span><?= e($u->apellido_materno ?: '—') ?></span>
                                </div>
                            </div>
                        </td>
                        <td><?= e($u->correo_usuario) ?></td>
                        <td><?= e($u->rol_usuario) ?></td>
                        <td><?= e($u->proyectos_count) ?></td>
                        <td>
                            <span class="status <?= e($u->estado_usuario === 1 ? 'activo' : 'pausado') ?>">
                                <?= e($u->estado_usuario === 1 ? 'Activo' : 'Inactivo') ?>
                            </span>
                        </td>
                        <td>
                            <button
                                type="button"
                                class="btn-actions btn-edit"
                                data-usuario="<?= e(json_encode([
                                    'url' => route('usuarios.update', $u),
                                    'nombre_usuario' => $u->nombre_usuario,
                                    'apellido_paterno' => $u->apellido_paterno,
                                    'apellido_materno' => $u->apellido_materno,
                                    'correo_usuario' => $u->correo_usuario,
                                    'rol_usuario' => $u->rol_usuario,
                                    'estado_usuario' => $u->estado_usuario,
                                ])) ?>"
                                onclick="abrirUsuario(JSON.parse(this.dataset.usuario))">
                                ✏️ Editar
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="pagination">
            <?= view('paginacion', ['paginador' => $usuarios, 'nombre' => 'usuarios']) ?>
        </div>
    </section>

</main>

<div class="modal-overlay" id="modalUsuario">
    <div class="modal-box">

        <div class="modal-header">
            <div>
                <h2 id="usuarioTitulo">Nuevo usuario</h2>
                <p id="usuarioSubtitulo">La contraseña debe tener al menos 8 caracteres.</p>
            </div>

            <button class="close-btn" onclick="cerrarUsuario()">✕</button>
        </div>

        <form method="POST" action="<?= e(route('usuarios.store')) ?>" id="formUsuario">
            <?= csrf_field() ?>
            <input type="hidden" name="_method" id="usuarioMetodo" value="POST">

            <div class="form-grid">
                <div class="form-group">
                    <label>Nombre</label>
                    <input type="text" name="nombre_usuario" id="u_nombre_usuario" value="<?= e(old('nombre_usuario')) ?>" required>
                </div>

                <div class="form-group">
                    <label>Apellido paterno</label>
                    <input type="text" name="apellido_paterno" id="u_apellido_paterno" value="<?= e(old('apellido_paterno')) ?>" required>
                </div>

                <div class="form-group">
                    <label>Apellido materno</label>
                    <input type="text" name="apellido_materno" id="u_apellido_materno" value="<?= e(old('apellido_materno')) ?>">
                </div>

                <div class="form-group">
                    <label>Correo electrónico</label>
                    <input type="email" name="correo_usuario" id="u_correo_usuario" value="<?= e(old('correo_usuario')) ?>" required>
                </div>

                <div class="form-group">
                    <label>Rol</label>
                    <select name="rol_usuario" id="u_rol_usuario">
                        <?php foreach (\App\Models\Usuario::ROLES as $rol): ?>
                            <option value="<?= e($rol) ?>" <?= (old('rol_usuario', 'Supervisor') === $rol) ? 'selected' : '' ?>><?= e($rol) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Estado</label>
                    <select name="estado_usuario" id="u_estado_usuario">
                        <option value="1" <?= (old('estado_usuario', '1') == '1') ? 'selected' : '' ?>>Activo</option>
                        <option value="0" <?= (old('estado_usuario') === '0') ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                </div>

                <div class="form-group">
                    <label id="u_label_password">Contraseña</label>
                    <input type="password" name="password" id="u_password" minlength="8" autocomplete="new-password" required>
                </div>

                <div class="form-group">
                    <label>Repite la contraseña</label>
                    <input type="password" name="password_confirmation" id="u_password_confirmation" minlength="8" autocomplete="new-password">
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="cerrarUsuario()">Cancelar</button>
                <button type="submit" class="btn-primary" id="usuarioBoton">Crear usuario</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirUsuario(usuario) {
    const form = document.getElementById("formUsuario");
    const editando = Boolean(usuario);
    const password = document.getElementById("u_password");

    form.action = editando ? usuario.url : "<?= e(route('usuarios.store')) ?>";
    document.getElementById("usuarioMetodo").value = editando ? "PUT" : "POST";
    document.getElementById("usuarioTitulo").textContent = editando ? "Editar usuario" : "Nuevo usuario";
    document.getElementById("usuarioSubtitulo").textContent = editando
        ? "Deja la contraseña en blanco para no cambiarla."
        : "La contraseña debe tener al menos 8 caracteres.";
    document.getElementById("usuarioBoton").textContent = editando ? "Guardar cambios" : "Crear usuario";
    document.getElementById("u_label_password").textContent = editando ? "Nueva contraseña (opcional)" : "Contraseña";
    password.required = !editando;
    password.value = "";
    document.getElementById("u_password_confirmation").value = "";

    if (editando) {
        ["nombre_usuario", "apellido_paterno", "apellido_materno", "correo_usuario", "rol_usuario", "estado_usuario"]
            .forEach(campo => document.getElementById("u_" + campo).value = usuario[campo] ?? "");
    }

    document.getElementById("modalUsuario").classList.add("show");
}

function cerrarUsuario() {
    document.getElementById("modalUsuario").classList.remove("show");
}

document.getElementById("modalUsuario").addEventListener("click", function(e) {
    if (e.target === this) cerrarUsuario();
});

<?php if ($errors->any() && old('_method', 'POST') === 'POST'): ?>
    abrirUsuario();
<?php endif; ?>
</script>

<?= view('pie') ?>
