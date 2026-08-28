<?php
/**
 * Central error handling for AID-U Technical Impact.
 *
 * Goal: a visitor (or the administrator) must never see a raw PHP error,
 * a stack trace, an SQL message or a blank white page. Every failure is
 * logged with a reference code and shown as a calm, branded page that
 * explains what happened and what to do next.
 *
 * This file must not depend on the database - it has to keep working when
 * the database itself is what failed.
 */
declare(strict_types=1);

/** Thrown for messages that are safe (and useful) to show to a person. */
class UserMessageException extends RuntimeException
{
    /** @var string[] Optional "what to do next" steps shown with the message. */
    private array $steps;

    public function __construct(string $message, array $steps = [], int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->steps = $steps;
    }

    /** @return string[] */
    public function steps(): array
    {
        return $this->steps;
    }
}

/**
 * Debug mode shows technical detail on screen. It is OFF unless the file
 * config/debug.flag exists or the APP_DEBUG environment variable is "1".
 * Never leave debug on for a live website.
 */
function app_debug(): bool
{
    static $debug = null;
    if ($debug !== null) {
        return $debug;
    }
    $env = getenv('APP_DEBUG');
    $debug = is_file(__DIR__ . '/debug.flag') || $env === '1' || $env === 'true';
    return $debug;
}

function app_log_dir(): string
{
    return dirname(__DIR__) . '/storage/logs';
}

/** Writes a log line and returns a short reference code to show the user. */
function app_log(string $level, string $message, ?Throwable $e = null): string
{
    $reference = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

    $line = sprintf(
        "[%s] %s ref=%s %s",
        date('Y-m-d H:i:s'),
        strtoupper($level),
        $reference,
        $message
    );

    if ($e !== null) {
        $line .= sprintf(
            " | %s: %s in %s:%d\n%s",
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
    }

    $line .= "\n" . str_repeat('-', 70) . "\n";

    $dir = app_log_dir();
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    if (is_dir($dir) && is_writable($dir)) {
        @file_put_contents($dir . '/app-' . date('Y-m') . '.log', $line, FILE_APPEND | LOCK_EX);
    } else {
        error_log($line);
    }

    return $reference;
}

/**
 * Turns any exception into wording a human can act on.
 *
 * @return array{0:string,1:string[],2:string} [message, steps, reference]
 */
function app_friendly_error(Throwable $e, string $fallback = 'Something went wrong on our side. Nothing you did caused this.'): array
{
    // Messages we wrote ourselves are safe to show word for word.
    if ($e instanceof UserMessageException) {
        $reference = method_exists($e, 'reference') ? (string) $e->reference() : '';
        return [$e->getMessage(), $e->steps(), $reference];
    }

    if ($e instanceof PDOException) {
        [$message, $steps] = app_database_advice($e);
        return [$message, $steps, app_log('error', 'Database failure', $e)];
    }

    $reference = app_log('error', 'Unhandled failure', $e);

    return [
        $fallback,
        [
            'Please try again in a moment.',
            'If it keeps happening, contact the website administrator and quote the reference code.',
        ],
        $reference,
    ];
}

/**
 * Explains common MySQL problems in plain language instead of showing
 * "SQLSTATE[42S02]: Base table or view not found ...".
 *
 * @return array{0:string,1:string[]}
 */
function app_database_advice(Throwable $e): array
{
    $text = $e->getMessage();
    $driverCode = 0;
    if ($e instanceof PDOException && isset($e->errorInfo[1])) {
        $driverCode = (int) $e->errorInfo[1];
    }

    // Connection-level problems report their code in the SQLSTATE string.
    $isRefused = str_contains($text, '2002') || stripos($text, 'refused') !== false
        || stripos($text, 'No such file or directory') !== false;
    // 1045 = bad password, 1044 = no rights on that database,
    // 1698 = MariaDB/MySQL refusing password auth for a socket-only account.
    $isDenied  = in_array($driverCode, [1044, 1045, 1698], true)
        || str_contains($text, '1045') || str_contains($text, '1044') || str_contains($text, '1698')
        || stripos($text, 'access denied') !== false;
    $noDb      = $driverCode === 1049 || str_contains($text, '1049');
    $noTable   = $driverCode === 1146 || str_contains($text, '42S02');
    $noColumn  = $driverCode === 1054 || str_contains($text, '42S22');

    if ($isRefused) {
        return [
            'The website cannot reach the database server right now.',
            [
                'Start MySQL in WAMP / XAMPP (the tray icon should be green).',
                'Check that the host and port in config/database.php match your server.',
                'Refresh this page once MySQL is running.',
            ],
        ];
    }

    if ($isDenied) {
        return [
            'The database refused the username or password the website is using.',
            [
                'Open config/database.php (or config/config.local.php) and confirm the database user and password.',
                'On a live host, use the database user created in cPanel, not "root".',
                'Make sure that user has been granted rights on the "aidutech" database.',
                'If the database itself has not been created yet, import database.sql in phpMyAdmin.',
            ],
        ];
    }

    if ($noDb) {
        return [
            'The website database has not been created yet.',
            [
                'Open phpMyAdmin and import the database.sql file included with this website.',
                'That file creates the "aidutech" database and all of its tables.',
                'Reload this page after the import finishes.',
            ],
        ];
    }

    if ($noTable) {
        return [
            'The website database is missing one or more of its tables.',
            [
                'Import database.sql again in phpMyAdmin to recreate the missing tables.',
                'Importing it will reset the sample content, so back up your data first if you already added some.',
            ],
        ];
    }

    if ($noColumn) {
        return [
            'The database structure is out of date compared with the website files.',
            [
                'Re-import database.sql so the tables match this version of the website.',
                'Back up any content you have already added before re-importing.',
            ],
        ];
    }

    return [
        'The website could not read or save information right now.',
        [
            'Please try again in a moment.',
            'If it keeps happening, tell the website administrator and quote the reference code.',
        ],
    ];
}

/**
 * Renders the branded error page. Self-contained on purpose: no database,
 * no external CSS, so it still renders when everything else is broken.
 *
 * @param string[] $steps
 */
function app_render_error_page(int $status, string $heading, string $message, array $steps = [], string $reference = '', ?Throwable $debugException = null): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
    }

    $esc = static fn (string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
    $home = '/';
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($script !== '') {
        $dir = rtrim(dirname($script), '/');
        if (basename($dir) === 'admin') {
            $dir = rtrim(dirname($dir), '/');
        }
        $home = ($dir === '' || $dir === '.') ? '/' : $dir . '/';
    }

    // Inline SVG rather than an emoji: emoji fonts differ between Windows,
    // Android and iOS, and some servers render them as an empty box.
    $svg = static fn (string $path): string =>
        '<svg viewBox="0 0 24 24" width="42" height="42" fill="none" stroke="currentColor" '
        . 'stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . $path . '</svg>';

    $icon = match (true) {
        $status === 404 => $svg('<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>'),
        $status === 403 => $svg('<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>'),
        $status === 419 => $svg('<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'),
        default => $svg('<path d="M12 3v3M12 18v3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M3 12h3M18 12h3M5.6 18.4l2.1-2.1M16.3 7.7l2.1-2.1"/><circle cx="12" cy="12" r="3.2"/>'),
    };

    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<meta name="robots" content="noindex">';
    echo '<title>' . $esc($heading) . ' &middot; AID-U Technical Impact</title>';
    echo '<style>
    :root{--ink:#050914;--card:#0b1630;--line:rgba(255,255,255,.10);--gold:#f5bd16;--gold2:#ffd94a;--blue:#006cff;--muted:#9aa7ba}
    *{box-sizing:border-box}
    body{margin:0;min-height:100vh;display:grid;place-items:center;padding:30px;
         background:radial-gradient(circle at 80% 15%,rgba(0,108,255,.18),transparent 40%),var(--ink);
         color:#eef5ff;font-family:"DM Sans",-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;line-height:1.65}
    .box{width:min(660px,100%);background:linear-gradient(180deg,#0b1730,#071125);border:1px solid var(--line);
         border-radius:18px;padding:38px;box-shadow:0 24px 80px rgba(0,0,0,.45)}
    .badge{display:inline-flex;align-items:center;gap:10px;font-size:12px;font-weight:800;letter-spacing:.18em;
           text-transform:uppercase;color:var(--gold2)}
    .badge:before{content:"";width:30px;height:2px;background:var(--gold)}
    .icon{margin:20px 0 4px;color:var(--gold);display:block;height:42px}
    h1{font-size:clamp(26px,4vw,38px);letter-spacing:-.03em;margin:8px 0 12px}
    p.lead{color:#c6d0e1;font-size:17px;margin:0 0 22px}
    .steps{margin:0 0 26px;padding:0;list-style:none;border-top:1px solid var(--line)}
    .steps li{display:flex;gap:12px;padding:12px 0;border-bottom:1px solid var(--line);color:#b6c2d4;font-size:15px}
    .steps li:before{content:"\2192";color:var(--gold);font-weight:800}
    .actions{display:flex;gap:12px;flex-wrap:wrap}
    a.btn{display:inline-flex;align-items:center;gap:9px;padding:13px 19px;border-radius:9px;font-weight:800;
          font-size:14px;text-decoration:none;color:#fff;background:linear-gradient(135deg,var(--blue),#0e8cff);
          border:1px solid rgba(255,255,255,.10)}
    a.btn.alt{background:rgba(255,255,255,.05);border-color:rgba(255,255,255,.18)}
    .ref{margin-top:24px;padding-top:18px;border-top:1px solid var(--line);color:#78859a;font-size:12.5px}
    .ref code{color:var(--gold2);font-size:13px;letter-spacing:.06em}
    details{margin-top:20px;background:rgba(255,73,73,.06);border:1px solid rgba(255,73,73,.28);border-radius:10px;padding:14px}
    summary{cursor:pointer;font-weight:800;color:#ff9b9b;font-size:13px}
    pre{white-space:pre-wrap;word-break:break-word;font-size:12px;color:#ffc9c9;margin:12px 0 0}
    @media(max-width:600px){.box{padding:26px}}
    </style></head><body><main class="box">';

    echo '<span class="badge">AID-U Technical Impact</span>';
    echo '<div class="icon">' . $icon . '</div>';
    echo '<h1>' . $esc($heading) . '</h1>';
    echo '<p class="lead">' . $esc($message) . '</p>';

    if ($steps !== []) {
        echo '<ul class="steps">';
        foreach ($steps as $step) {
            echo '<li><span>' . $esc((string) $step) . '</span></li>';
        }
        echo '</ul>';
    }

    echo '<div class="actions">';
    echo '<a class="btn" href="' . $esc($home) . '">Go to the homepage</a>';
    echo '<a class="btn alt" href="' . $esc($home) . 'contact.php">Contact us</a>';
    echo '</div>';

    if ($reference !== '') {
        echo '<p class="ref">Reference code <code>' . $esc($reference) . '</code> &mdash; quote this when reporting the problem. '
           . 'The technical detail was saved to <code>storage/logs</code>.</p>';
    }

    if ($debugException !== null && app_debug()) {
        echo '<details open><summary>Technical detail (debug mode is on)</summary><pre>'
            . $esc(get_class($debugException) . ': ' . $debugException->getMessage())
            . "\n" . $esc($debugException->getFile() . ':' . $debugException->getLine())
            . "\n\n" . $esc($debugException->getTraceAsString())
            . '</pre></details>';
    }

    echo '</main></body></html>';
}

/** Shorthand for the common "page not found" screen. */
function app_not_found(string $what = 'page'): void
{
    app_render_error_page(
        404,
        'We could not find that ' . $what,
        'The ' . $what . ' you asked for does not exist, or it was removed from the website.',
        [
            'Check the address for a typing mistake.',
            'Use the menu on the homepage to find what you need.',
            'Browse the project library from the Projects page.',
        ]
    );
    exit;
}

/** Last line of defence for anything that escapes a try/catch. */
function app_handle_exception(Throwable $e): void
{
    [$message, $steps, $reference] = app_friendly_error($e);

    $heading = ($e instanceof PDOException || (class_exists('DatabaseUnavailableException', false) && $e instanceof DatabaseUnavailableException))
        ? 'The website needs a quick fix'
        : 'Something went wrong';

    app_render_error_page(500, $heading, $message, $steps, $reference, $e);
    exit;
}

/** Converts warnings/notices into exceptions so they never print into the page. */
function app_handle_error(int $severity, string $message, string $file = '', int $line = 0): bool
{
    if (!(error_reporting() & $severity)) {
        return false;
    }

    // Notices and deprecations are logged but must not break a working page.
    if (in_array($severity, [E_NOTICE, E_USER_NOTICE, E_DEPRECATED, E_USER_DEPRECATED, E_WARNING, E_USER_WARNING], true)) {
        app_log('warning', sprintf('%s in %s:%d', $message, $file, $line));
        return true;
    }

    throw new ErrorException($message, 0, $severity, $file, $line);
}

/** Catches fatal errors (out of memory, parse-time failures) that bypass handlers. */
function app_handle_shutdown(): void
{
    $error = error_get_last();
    if ($error === null || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        return;
    }

    $reference = app_log(
        'fatal',
        sprintf('%s in %s:%d', $error['message'], $error['file'], $error['line'])
    );

    app_render_error_page(
        500,
        'The website stopped unexpectedly',
        'A serious error interrupted this page. It has been recorded so it can be fixed.',
        [
            'Please reload the page.',
            'If the problem repeats, send the reference code to the website administrator.',
        ],
        $reference
    );
}

// Errors are always recorded, never printed straight into the page.
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');

set_error_handler('app_handle_error');
set_exception_handler('app_handle_exception');
register_shutdown_function('app_handle_shutdown');
