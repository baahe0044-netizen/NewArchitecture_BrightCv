<?php
declare(strict_types=1);

require_once __DIR__ . '/errors.php';
require_once __DIR__ . '/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => (($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off'),
    ]);
    session_start();
}

function e(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function base_path(): string {
    static $base = null;
    if ($base !== null) return $base;
    $script = str_replace('\\','/', $_SERVER['SCRIPT_NAME'] ?? '');
    $dir = str_replace('\\','/', dirname($script));
    if (basename($dir) === 'admin') $dir = dirname($dir);
    $base = rtrim($dir, '/');
    if ($base === '.' || $base === '\\') $base = '';
    return $base;
}
function url(string $path=''): string { return base_path() . '/' . ltrim($path,'/'); }
function asset_url(string $path=''): string { if ($path === '') return ''; if (preg_match('~^https?://~i', $path)) return $path; return url($path); }

/**
 * Site settings, read once per request. A database problem must not blank the
 * whole website, so the fallback value is used and the cause is logged.
 */
function site_setting(string $key, string $fallback=''): string {
    static $s = null;
    if ($s === null) {
        try {
            $s = db()->query('SELECT * FROM settings WHERE id=1')->fetch() ?: [];
        } catch (Throwable $e) {
            app_log('warning', 'Could not load site settings, using defaults', $e);
            $s = [];
        }
    }
    return isset($s[$key]) && $s[$key] !== null ? (string)$s[$key] : $fallback;
}

function socials(): array {
    return db_rows('SELECT * FROM social_links WHERE active=1 ORDER BY sort_order,id');
}

function whatsapp_url(string $message=''): string {
    $n = normalize_gh_phone(site_setting('whatsapp', site_setting('phone','')));
    if ($n === '') return '';
    return 'https://wa.me/'.$n.($message!==''?'?text='.rawurlencode($message):'');
}

function normalize_gh_phone(string $phone): string {
    $phone = preg_replace('/\D+/', '', $phone) ?? '';
    if ($phone === '') {
        return '';
    }
    if (str_starts_with($phone, '233')) {
        return $phone;
    }
    if (str_starts_with($phone, '0')) {
        return '233' . substr($phone, 1);
    }
    return $phone;
}

function csrf_token(): string { if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }

/**
 * Checks the security token. A stale token almost always means the person
 * left the form open too long, so the page explains that instead of printing
 * "Security check failed." on a blank screen.
 */
function verify_csrf(): void {
    if (hash_equals((string)($_SESSION['csrf'] ?? ''), (string)($_POST['csrf_token'] ?? ''))) {
        return;
    }

    app_log('warning', 'CSRF token mismatch on ' . ($_SERVER['REQUEST_URI'] ?? 'unknown'));

    app_render_error_page(
        419,
        'Your session timed out',
        'For your security this form expired before it was submitted, so nothing was saved or sent.',
        [
            'Go back and open the form again.',
            'Fill it in and submit it within the same browsing session.',
            'Make sure cookies are enabled in your browser.',
        ]
    );
    exit;
}

function flash(string $type,string $message):void{$_SESSION['flash'][]=[$type,$message];}
function get_flashes():array{$x=$_SESSION['flash']??[];unset($_SESSION['flash']);return $x;}

/**
 * Converts a caught exception into a flash message a person can act on.
 * Validation wording is shown as written; anything technical becomes a calm
 * sentence plus a reference code, with the detail written to the log.
 */
function flash_exception(Throwable $e, string $fallback = 'That action could not be completed.'): void {
    [$message, $steps, $reference] = app_friendly_error($e, $fallback);
    if ($steps !== []) {
        $message .= ' ' . implode(' ', $steps);
    }
    if ($reference !== '') {
        $message .= ' (Reference: ' . $reference . ')';
    }
    flash('error', $message);
}

/** Same idea, but for pages that render the message inline instead of flashing. */
function inline_exception_message(Throwable $e, string $fallback = 'That action could not be completed.'): string {
    [$message, $steps, $reference] = app_friendly_error($e, $fallback);
    if ($steps !== []) {
        $message .= ' ' . implode(' ', $steps);
    }
    if ($reference !== '') {
        $message .= ' (Reference: ' . $reference . ')';
    }
    return $message;
}

function admin_logged_in():bool{return !empty($_SESSION['admin_id']);}

function require_admin():void{
    if(!admin_logged_in()){
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            // Never silently discard a submitted form: say what happened.
            app_render_error_page(
                403,
                'You are signed out',
                'Your administrator session has ended, so this change was not saved.',
                [
                    'Sign in again from the admin login page.',
                    'Reopen the form and submit your change once more.',
                ]
            );
            exit;
        }
        $_SESSION['admin_redirect'] = $_SERVER['REQUEST_URI'] ?? '';
        header('Location: '.url('admin/login.php'));
        exit;
    }
}

function admin_count():int{ return (int) db_value('SELECT COUNT(*) FROM users', [], 0); }

/** Simple login throttle so passwords cannot be guessed at speed. */
function login_attempts_exceeded(int $limit = 6, int $window = 900): bool {
    $log = $_SESSION['login_attempts'] ?? [];
    $now = time();
    $log = array_values(array_filter($log, static fn($t) => ($now - (int)$t) < $window));
    $_SESSION['login_attempts'] = $log;
    return count($log) >= $limit;
}
function record_login_failure(): void { $_SESSION['login_attempts'][] = time(); }
function clear_login_failures(): void { unset($_SESSION['login_attempts']); }

function delete_file(string $relative):void{
    if($relative==='')return;
    $relative=ltrim($relative,'/');
    if(str_starts_with($relative,'assets/images/')) return; // never remove shipped artwork
    if(str_contains($relative,'..')) return;
    $p=dirname(__DIR__).'/'.$relative;
    if(is_file($p)) @unlink($p);
}

function upload_error(int $e):string{
    return match($e){
        UPLOAD_ERR_INI_SIZE=>'That file is larger than the server allows. Try a smaller file, or raise upload_max_filesize in php.ini.',
        UPLOAD_ERR_FORM_SIZE=>'That file is larger than this form allows. Please choose a smaller file.',
        UPLOAD_ERR_PARTIAL=>'The upload was interrupted. Check your connection and try again.',
        UPLOAD_ERR_NO_TMP_DIR=>'The server has no temporary upload folder configured. Ask your host to set upload_tmp_dir.',
        UPLOAD_ERR_CANT_WRITE=>'The server could not write the file to disk. Check the folder permissions on uploads/.',
        UPLOAD_ERR_EXTENSION=>'A PHP extension blocked this upload. Check the server error log for details.',
        default=>'The file could not be uploaded. Please try again.'
    };
}

/** Keeps uploaded folders from ever executing PHP. */
function protect_upload_dir(string $folder): void {
    $htaccess = $folder . '/.htaccess';
    if (is_file($htaccess)) return;
    @file_put_contents($htaccess, "Options -Indexes\n<FilesMatch \"\\.(php|phtml|php[0-9]*|pl|py|cgi|shtml)$\">\n    Require all denied\n</FilesMatch>\n");
}

function save_upload(array $file,string $dir,array $mimes,int $maxBytes,bool $optimizeImage=false):?string{
    if(!isset($file['error'])||$file['error']===UPLOAD_ERR_NO_FILE)return null;
    if($file['error']!==UPLOAD_ERR_OK)throw new UserMessageException(upload_error((int)$file['error']));
    if((int)$file['size']>$maxBytes)throw new UserMessageException('That file is too large. The maximum is '.number_format($maxBytes/1048576,0).' MB — please compress it or choose a smaller file.');
    if(!is_uploaded_file($file['tmp_name']))throw new UserMessageException('That upload could not be verified. Please select the file again.');

    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if($mime===false||!isset($mimes[$mime])){
        throw new UserMessageException('That file type is not accepted here. Allowed types: '.implode(', ',array_values($mimes)).'.');
    }

    $ext=$mimes[$mime];
    $root=dirname(__DIR__);
    $folder=$root.'/'.trim($dir,'/');
    if(!is_dir($folder)&&!@mkdir($folder,0755,true)&&!is_dir($folder)){
        throw new UserMessageException('The upload folder "'.trim($dir,'/').'" could not be created. Give the web server write permission to that folder.');
    }
    if(!is_writable($folder)){
        throw new UserMessageException('The upload folder "'.trim($dir,'/').'" is not writable. Set write permission on it and try again.');
    }
    protect_upload_dir($folder);

    if($optimizeImage && function_exists('imagecreatefromstring')){
        $raw=@file_get_contents($file['tmp_name']);
        $im=$raw===false?false:@imagecreatefromstring($raw);
        if($im!==false){
            // Re-encoded as JPEG, so the saved name must end in .jpg — using the
            // original extension here produced files whose contents did not match.
            $name=bin2hex(random_bytes(12)).'.jpg';
            $dest=$folder.'/'.$name;
            $w=imagesx($im);$h=imagesy($im);$maxW=2400;$scale=min(1,$maxW/max(1,$w));
            $nw=max(1,(int)round($w*$scale));$nh=max(1,(int)round($h*$scale));
            $out=imagecreatetruecolor($nw,$nh);
            imagecopyresampled($out,$im,0,0,0,0,$nw,$nh,$w,$h);
            $ok=imagejpeg($out,$dest,82);
            imagedestroy($out);imagedestroy($im);
            if($ok){ return trim($dir,'/').'/'.$name; }
            @unlink($dest);
            // Fall through and store the original file instead of losing the upload.
        }
    }

    $name=bin2hex(random_bytes(12)).'.'.$ext;
    $dest=$folder.'/'.$name;
    if(!move_uploaded_file($file['tmp_name'],$dest))throw new UserMessageException('The file could not be saved on the server. Check that the uploads folder is writable.');
    return trim($dir,'/').'/'.$name;
}

function save_multiple(array $files,string $dir,array $mimes,int $maxBytes,bool $image=false):array{
    $out=[];
    if(!isset($files['name'])||!is_array($files['name']))return $out;
    foreach(array_keys($files['name']) as $i){
        $f=[
            'name'=>$files['name'][$i]??'',
            'type'=>$files['type'][$i]??'',
            'tmp_name'=>$files['tmp_name'][$i]??'',
            'error'=>$files['error'][$i]??UPLOAD_ERR_NO_FILE,
            'size'=>$files['size'][$i]??0,
        ];
        if($f['error']===UPLOAD_ERR_NO_FILE)continue;
        $p=save_upload($f,$dir,$mimes,$maxBytes,$image);
        if($p)$out[]=$p;
    }
    return $out;
}

function media_type_for(string $path):string{$e=strtolower(pathinfo($path,PATHINFO_EXTENSION));return match($e){'mp4'=>'video/mp4','webm'=>'video/webm','mov'=>'video/quicktime',default=>'video/mp4'};}

function social_icon(string $platform,string $stored=''):string{return $stored!==''?$stored:match(strtolower(trim($platform))){'facebook'=>'fa-brands fa-facebook-f','instagram'=>'fa-brands fa-instagram','linkedin'=>'fa-brands fa-linkedin-in','youtube'=>'fa-brands fa-youtube','tiktok'=>'fa-brands fa-tiktok','x','twitter'=>'fa-brands fa-x-twitter','whatsapp'=>'fa-brands fa-whatsapp','telegram'=>'fa-brands fa-telegram','pinterest'=>'fa-brands fa-pinterest-p',default=>'fa-solid fa-link'};}

/** Trimmed POST value that never raises "Undefined array key". */
function post_text(string $key, string $default=''): string {
    $v = $_POST[$key] ?? $default;
    return trim(is_scalar($v) ? (string)$v : $default);
}
function post_int(string $key, int $default=0): int {
    $v = $_POST[$key] ?? $default;
    return is_numeric($v) ? (int)$v : $default;
}
