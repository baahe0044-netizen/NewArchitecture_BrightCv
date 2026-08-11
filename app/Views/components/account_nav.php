<?php
/** @var string $active */
?>
<aside class="card account-nav">
    <a class="<?= $active === 'profile' ? 'active' : '' ?>" href="<?= e(base_url('/account/profile')) ?>"><span>○</span><div><b>Profile</b><small>Name and preferences</small></div></a>
    <a class="<?= $active === 'security' ? 'active' : '' ?>" href="<?= e(base_url('/account/security')) ?>"><span>⌁</span><div><b>Security</b><small>Password and sessions</small></div></a>
    <a class="<?= $active === 'appearance' ? 'active' : '' ?>" href="<?= e(base_url('/account/appearance')) ?>"><span>◐</span><div><b>Appearance</b><small>Theme</small></div></a>
    <a href="<?= e(base_url('/dashboard')) ?>"><span>←</span><div><b>Dashboard</b><small>Return to your CVs</small></div></a>
</aside>
