<?= view('encabezado_acceso', ['titulo' => 'ConstructControl — Recuperar contraseña']) ?>

        <div class="form-header">
            <h1 class="form-title">Recuperar contraseña</h1>
            <p class="form-subtitle">Ingresa tu correo y te enviaremos un enlace para crear una nueva contraseña.</p>
        </div>

        <?php if (session('exito')): ?>
            <div class="ok-box">✅ <?= e(session('exito')) ?></div>
        <?php endif; ?>

        <?php if ($errors->has('email')): $message = $errors->first('email'); ?>
            <div class="error-box">⚠️ <?= e($message) ?></div>
        <?php endif; ?>

        <form action="<?= e(route('password.email')) ?>" method="POST">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <div class="input-wrap">
                    <input id="email" name="email" type="email" value="<?= e(old('email')) ?>" placeholder="usuario@empresa.cl" required autofocus>
                </div>
            </div>

            <button type="submit" class="btn-login">Enviar enlace</button>
        </form>

        <div class="form-options centrado" style="margin-top:18px;">
            <a href="<?= e(route('login')) ?>" class="forgot">← Volver a iniciar sesión</a>
        </div>

<?= view('pie_acceso') ?>
