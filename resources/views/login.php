<?= view('encabezado_acceso', ['titulo' => 'ConstructControl — Iniciar sesión']) ?>

        <div class="form-header">

            <h1 class="form-title">
                Iniciar sesión
            </h1>

        </div>

        <!-- Mensaje de error -->
        <?php if (session('exito')): ?>

            <div class="ok-box">
                ✅ <?= e(session('exito')) ?>
            </div>

        <?php endif; ?>

        <?php if ($errors->has('email')): $message = $errors->first('email'); ?>

            <div class="error-box">
                ⚠️ <?= e($message) ?>
            </div>

        <?php endif; ?>


        <!-- FORMULARIO -->
        <form action="<?= e(route('login.store')) ?>" method="POST">
            <?= csrf_field() ?>

            <div class="form-group">

                <label for="email">
                    Correo electrónico
                </label>

                <div class="input-wrap">

                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="<?= e(old('email')) ?>"
                        placeholder="usuario@empresa.cl"
                        required
                    >

                </div>

            </div>


            <div class="form-group">

                <label for="password">
                    Contraseña
                </label>

                <div class="input-wrap">

                    <input
                        id="password"
                        name="password"
                        type="password"
                        placeholder="••••••••"
                        required
                    >

                    <span class="input-icon">
                        👁
                    </span>

                </div>

            </div>


            <div class="form-options">

                <label class="checkbox-wrap">

                    <input type="checkbox" name="recordar" value="1" checked>

                    Mantener sesión iniciada

                </label>


                <a href="<?= e(route('password.request')) ?>" class="forgot">

                    ¿Olvidaste tu contraseña?

                </a>

            </div>


            <button type="submit" class="btn-login">

                Ingresar al sistema

            </button>

        </form>

<?= view('pie_acceso') ?>
