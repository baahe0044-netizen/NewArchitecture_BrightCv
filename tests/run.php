<?php

declare(strict_types=1);

$sessionPath = sys_get_temp_dir() . '/lunettistar-test-sessions';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}
ini_set('session.save_path', $sessionPath);

require_once dirname(__DIR__) . '/config/app.php';

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

assertTrue(class_exists(App::class), 'The application class should be available through the application autoloader.');
assertTrue(class_exists(Database::class), 'The database class should be available through the application autoloader.');

$content = [
    'personal' => [
        'full_name' => 'Emmanuel Baah',
        'email' => 'emmanuel@example.com',
        'phone' => '+233 24 000 0000',
        'location' => 'Accra, Ghana',
    ],
    'summary' => str_repeat('Experienced software engineer delivering reliable applications and measurable results. ', 2),
    'experience' => [[
        'role' => 'Software Engineer',
        'company' => 'Example Ltd',
        'bullets' => [
            'Improved order completion by 25% through a streamlined checkout.',
            'Developed a secure inventory service used by 5 employees.',
        ],
    ]],
    'education' => [['degree' => 'Diploma in Software Engineering', 'school' => 'IPMC']],
    'skills' => array_map(static fn (string $name): array => ['name' => $name], ['PHP', 'MySQL', 'Python', 'React', 'JavaScript', 'Git']),
];

$report = (new AtsService())->analyze($content, 'We need a software engineer with PHP, MySQL, JavaScript, Git and inventory experience.');
assertTrue($report['score'] >= 70, 'A complete, relevant CV should achieve a strong ATS score.');
assertTrue(in_array('php', $report['matched_keywords'], true), 'PHP should be detected as a matched keyword.');

$validator = new Validator();
assertTrue(!$validator->validate(['email' => 'not-an-email'], ['email' => 'required|email']), 'Invalid email should fail validation.');
assertTrue(isset($validator->errors()['email']), 'Email validation should expose a field error.');

$request = new Request(
    ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => BASE_PATH . '/dashboard', 'HTTP_ACCEPT' => 'text/html'],
    [],
    [],
    [],
    []
);
assertTrue($request->path() === '/dashboard', 'Request should remove the configured application base path.');
assertTrue(strlen(Csrf::token()) === 64, 'CSRF tokens should contain 32 random bytes.');

$typedRequest = new Request(
    ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => BASE_PATH . '/test'],
    [],
    ['unsafe' => ['nested'], 'enabled' => 'on', 'count' => 3],
    [],
    []
);
assertTrue($typedRequest->string('unsafe', 'fallback') === 'fallback', 'String input should reject nested values.');
assertTrue($typedRequest->string('count') === '3', 'String input should safely accept numeric values.');
assertTrue($typedRequest->boolean('enabled'), 'Boolean input should understand HTML checkbox values.');

$router = new Router();
$router->get('/health/{name}', static fn (Request $request, string $name): Response => Response::json([
    'success' => true,
    'name' => $name,
]));
$routeRequest = new Request(
    ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => BASE_PATH . '/health/lunetti', 'HTTP_ACCEPT' => 'application/json'],
    [],
    [],
    [],
    []
);
ob_start();
$router->dispatch($routeRequest)->send();
$routePayload = json_decode((string) ob_get_clean(), true);
assertTrue(($routePayload['name'] ?? null) === 'lunetti', 'Router should bind named path parameters.');

$assistant = (new AIService())->run('bullet', [
    'text' => 'responsible for daily customer support',
    'outcome' => 'a 20% faster response time',
], ['content' => []]);
assertTrue(str_contains($assistant['text'], '20%'), 'Achievement writer should preserve a supplied measurable result.');

echo "PHP domain tests passed.\n";
