<?php
/**
 * Database connection.
 *
 * Credentials can be overridden without touching this file: copy
 * config/config.sample.php to config/config.local.php and edit that.
 * config.local.php is ignored by git so live passwords never get committed.
 */
declare(strict_types=1);

require_once __DIR__ . '/errors.php';

/** Raised when the database cannot be reached at all. */
class DatabaseUnavailableException extends UserMessageException
{
    public function __construct(
        string $message,
        array $steps,
        private string $reference = '',
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $steps, 0, $previous);
    }

    public function reference(): string
    {
        return $this->reference;
    }
}

function db_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $config = [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'aidutech',
        'user' => 'root',
        'pass' => '',
    ];

    $local = __DIR__ . '/config.local.php';
    if (is_file($local)) {
        $overrides = require $local;
        if (is_array($overrides)) {
            $config = array_merge($config, array_intersect_key($overrides, $config));
        }
    }

    foreach (['DB_HOST' => 'host', 'DB_PORT' => 'port', 'DB_NAME' => 'name', 'DB_USER' => 'user', 'DB_PASS' => 'pass'] as $env => $key) {
        $value = getenv($env);
        if ($value !== false && $value !== '') {
            $config[$key] = $value;
        }
    }

    $config['port'] = (int) $config['port'];

    return $config;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $c = db_config();
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'], $c['port'], $c['name']);

    try {
        $pdo = new PDO($dsn, (string) $c['user'], (string) $c['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);
    } catch (PDOException $e) {
        // The raw message can contain the DSN and username, so it is logged
        // rather than shown. The visitor gets plain-language instructions.
        [$message, $steps] = app_database_advice($e);
        $reference = app_log('error', 'Database connection failed', $e);

        throw new DatabaseUnavailableException($message, $steps, $reference, $e);
    }

    return $pdo;
}

/** True when the database can be reached; used for graceful page fallbacks. */
function db_available(): bool
{
    static $available = null;
    if ($available !== null) {
        return $available;
    }
    try {
        db();
        $available = true;
    } catch (Throwable) {
        $available = false;
    }
    return $available;
}

/**
 * Runs a read query and returns [] instead of crashing the whole page when
 * the database hiccups. Use for content lists; a failure here should degrade
 * a section, not take the site down.
 */
function db_rows(string $sql, array $params = []): array
{
    try {
        if ($params === []) {
            return db()->query($sql)->fetchAll();
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        app_log('warning', 'Content query failed: ' . $sql, $e);
        return [];
    }
}

/** Single-value read with a safe fallback. */
function db_value(string $sql, array $params = [], mixed $fallback = 0): mixed
{
    try {
        if ($params === []) {
            $value = db()->query($sql)->fetchColumn();
        } else {
            $stmt = db()->prepare($sql);
            $stmt->execute($params);
            $value = $stmt->fetchColumn();
        }
        return $value === false ? $fallback : $value;
    } catch (Throwable $e) {
        app_log('warning', 'Count query failed: ' . $sql, $e);
        return $fallback;
    }
}

/** Single-row read with a safe fallback. */
function db_row(string $sql, array $params = []): ?array
{
    try {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    } catch (Throwable $e) {
        app_log('warning', 'Row query failed: ' . $sql, $e);
        return null;
    }
}

/** Tables the website cannot run without. */
const APP_REQUIRED_TABLES = [
    'users', 'settings', 'services', 'sectors',
    'social_links', 'projects', 'project_media',
    'testimonials', 'contact_messages',
];

/**
 * Returns the required tables that are absent, so a half-finished import is
 * reported as "import database.sql" rather than silently blanking the site.
 * The answer is cached in the session for a minute to keep the cost near zero.
 */
function db_missing_tables(bool $forceRefresh = false): array
{
    static $missing = null;
    if ($missing !== null && !$forceRefresh) {
        return $missing;
    }

    $cache = $_SESSION['schema_check'] ?? null;
    if (!$forceRefresh && is_array($cache) && (time() - (int) ($cache['at'] ?? 0)) < 60) {
        return $missing = $cache['missing'];
    }

    try {
        $present = db()->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e) {
        app_log('warning', 'Could not list database tables', $e);
        return $missing = [];   // a connection problem is reported elsewhere
    }

    $present = array_map('strtolower', $present);
    $missing = array_values(array_diff(APP_REQUIRED_TABLES, $present));

    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['schema_check'] = ['at' => time(), 'missing' => $missing];
    }

    return $missing;
}

/** Renders the "finish the setup" page when required tables are absent. */
function db_require_schema(): void
{
    $missing = db_missing_tables();
    if ($missing === []) {
        return;
    }

    app_log('error', 'Missing database tables: ' . implode(', ', $missing));

    app_render_error_page(
        503,
        'The website setup is not finished',
        'The database is connected, but ' . count($missing) . ' of its tables '
            . (count($missing) === 1 ? 'is' : 'are') . ' missing: ' . implode(', ', $missing) . '.',
        [
            'Open phpMyAdmin and select the "aidutech" database.',
            'Use the Import tab to import the database.sql file supplied with this website.',
            'Back up any content you have already added first, because the import recreates these tables.',
            'Reload this page once the import has finished.',
        ]
    );
    exit;
}
