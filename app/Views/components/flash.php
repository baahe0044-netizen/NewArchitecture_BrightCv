<?php if (!empty($message)): ?>
    <div class="alert alert-success" role="status"><?= e($message) ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
<?php endif; ?>
