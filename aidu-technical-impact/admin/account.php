<?php
require __DIR__.'/../config/bootstrap.php';
require_admin();
$admin_title='Admin Account';
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        verify_csrf();
        $action=$_POST['action']??'';
        $id=(int)$_SESSION['admin_id'];
        if($action==='update'){
            $name=post_text('full_name');
            $username=post_text('username');
            $password=(string)($_POST['password']??'');
            if($name===''||$username==='') throw new UserMessageException('Both your full name and a username are required.');
            $check=db()->prepare('SELECT id FROM users WHERE username=? AND id<>? LIMIT 1');
            $check->execute([$username,$id]);
            if($check->fetch()) throw new UserMessageException('That username is already in use. Please choose a different one.');
            if($password!==''){
                if(strlen($password)<10) throw new UserMessageException('A new password must be at least 10 characters long. Leave the field blank to keep your current password.');
                $st=db()->prepare('UPDATE users SET full_name=?, username=?, password_hash=? WHERE id=?');
                $st->execute([$name,$username,password_hash($password,PASSWORD_DEFAULT),$id]);
            }else{
                $st=db()->prepare('UPDATE users SET full_name=?, username=? WHERE id=?');
                $st->execute([$name,$username,$id]);
            }
            $_SESSION['admin_name']=$name;
            flash('success','Administrator account updated successfully.');
            header('Location: '.url('admin/account.php')); exit;
        }
        if($action==='delete'){
            $confirm=post_text('confirm_delete');
            if($confirm!=='DELETE') throw new UserMessageException('Type DELETE in capital letters to confirm that you want to remove this account.');
            db()->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
            $_SESSION=[];
            session_regenerate_id(true);
            flash('success','Administrator account deleted. You can register a new administrator.');
            header('Location: '.url('admin/register.php')); exit;
        }
    }catch(Throwable $e){
        $error=inline_exception_message($e,'Your account could not be updated just now. Please try again.');
    }
}
$user = db_row('SELECT id,username,full_name,created_at FROM users WHERE id=? LIMIT 1', [(int)$_SESSION['admin_id']]) ?? [];
if ($user === []) {
    // The signed-in account no longer exists (deleted in another tab or by SQL).
    $_SESSION = [];
    flash('error', 'That administrator account no longer exists, so you have been signed out.');
    header('Location: ' . url('admin/login.php'));
    exit;
}
require __DIR__.'/../includes/admin_header.php';
?>
<?php if($error): ?><div class="admin-alert error" role="alert"><i class="fa-solid fa-circle-exclamation"></i> <?=e($error)?></div><?php endif; ?>
<section class="admin-card account-hero-card">
    <div class="account-avatar"><i class="fa-solid fa-user-shield"></i></div>
    <div><span class="section-mini-label">SECURITY & ACCESS</span><h2><?=e($user['full_name']??'Administrator')?></h2><p>Manage the administrator account used to control the AID-U website.</p></div>
</section>
<section class="admin-card">
    <div class="admin-card-head"><div><h2>Edit administrator account</h2><p>Update your name, username or password. Leave the password blank if you want to keep the current password.</p></div></div>
    <form class="admin-form" method="post">
        <input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="update">
        <div class="form-cols">
            <div class="field"><label>Full name</label><input name="full_name" required value="<?=e($user['full_name']??'')?>"></div>
            <div class="field"><label>Username</label><input name="username" required value="<?=e($user['username']??'')?>"></div>
            <div class="field"><label>New password</label><input type="password" name="password" minlength="10" placeholder="Leave blank to keep current password"></div>
            <div class="field"><label>Account created</label><input value="<?=e($user['created_at']??'')?>" readonly></div>
        </div>
        <button class="admin-button" type="submit"><i class="fa-solid fa-floppy-disk"></i> Save Account Changes</button>
    </form>
</section>
<section class="admin-card danger-card">
    <div class="admin-card-head"><div><h2>Delete administrator account</h2><p>This removes your administrator account and ends the current session. You can create a new administrator afterward.</p></div></div>
    <form method="post" onsubmit="return confirm('Delete this administrator account? You will be signed out immediately.');" class="delete-account-form">
        <input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="delete">
        <div class="field"><label>Type DELETE to confirm</label><input name="confirm_delete" required autocomplete="off" placeholder="DELETE"></div>
        <button class="danger-button" type="submit"><i class="fa-solid fa-trash"></i> Delete Account</button>
    </form>
</section>
<?php require __DIR__.'/../includes/admin_footer.php'; ?>
