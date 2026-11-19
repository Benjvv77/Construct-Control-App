<?php if (session('exito')): ?>
    <div class="alert success"><?= e(session('exito')) ?></div>
<?php endif; ?>

<?php if ($errors->any()): ?>
    <div class="alert error">
        <?php if ($errors->count() === 1): ?>
            <?= e($errors->first()) ?>
        <?php else: ?>
            <ul>
                <?php foreach ($errors->all() as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
<?php endif; ?>
