<?= view('encabezado_acceso', ['titulo' => 'ConstructControl — Nueva contraseña']) ?>

        <div class="form-header">
            <h1 class="form-title">Nueva contraseña</h1>
            <p class="form-subtitle">Elige una contraseña de al menos 8 caracteres.</p>
        </div>

        <?php if ($errors->any()): ?>
            <div class="error-box">⚠️ <?= e($errors->first()) ?></div>
        <?php endif; ?>

        <form action="<?= e(route('password.update')) ?>" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">

            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <div class="input-wrap">
                    <input id="email" name="email" type="email" value="<?= e(old('email', $email)) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Nueva contraseña</label>
                <div class="input-wrap">
                    <input id="password" name="password" type="password" minlength="8" required autofocus>
                </div>
            </div>

            <div class="form-group">
                <label for="password_confirmation">Repite la contraseña</label>
                <div class="input-wrap">
                    <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" required>
                </div>
            </div>

            <button type="submit" class="btn-login">Guardar contraseña</button>
        </form>

<?= view('pie_acceso') ?>
