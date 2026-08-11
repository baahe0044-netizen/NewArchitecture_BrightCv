<?php

declare(strict_types=1);

final class AuthService
{
    public function __construct(private readonly UserRepository $users = new UserRepository())
    {
    }

    public function login(string $email, string $password, string $ip, bool $remember = false): array
    {
        $email = mb_strtolower(trim($email));
        $limiterKey = 'login|' . $email . '|' . $ip;

        if (RateLimiter::tooManyAttempts($limiterKey, 5)) {
            return [
                'success' => false,
                'message' => 'Too many sign-in attempts. Try again in ' . RateLimiter::availableIn($limiterKey) . ' seconds.',
            ];
        }

        $validShape = filter_var($email, FILTER_VALIDATE_EMAIL) !== false
            && mb_strlen($email) <= 190
            && $password !== ''
            && strlen($password) <= 128;
        $user = $validShape ? $this->users->findByEmail($email) : null;
        $verificationHash = (string) ($user['password_hash']
            ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
        $passwordMatches = password_verify($validShape ? $password : '', $verificationHash);
        if (!$user || !$passwordMatches) {
            RateLimiter::hit($limiterKey, 900);
            usleep(250000);
            return ['success' => false, 'message' => 'The email or password is incorrect.'];
        }

        RateLimiter::clear($limiterKey);
        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            $this->users->updatePasswordHash((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
        }

        $this->users->updateLastLogin((int) $user['id']);
        Auth::login($user, $remember);
        (new ActivityRepository())->record((int) $user['id'], 'signed_in', 'Signed in to BrightCV');

        unset($user['password_hash']);
        return ['success' => true, 'user' => $user];
    }

    public function register(array $data): array
    {
        $data = [
            'name' => trim(strip_tags($this->stringValue($data['name'] ?? ''))),
            'email' => mb_strtolower(trim($this->stringValue($data['email'] ?? ''))),
            'password' => $this->stringValue($data['password'] ?? ''),
            'password_confirmation' => $this->stringValue($data['password_confirmation'] ?? ''),
        ];
        $validator = new Validator();
        $valid = $validator->validate($data, [
            'name' => 'required|min:2|max:100',
            'email' => 'required|email|max:190',
            'password' => 'required|min:8|max:128',
        ]);

        $errors = $validator->errors();
        $password = (string) ($data['password'] ?? '');

        if ($password !== (string) ($data['password_confirmation'] ?? '')) {
            $errors['password_confirmation'][] = 'The password confirmation does not match.';
        }

        if ($password !== '' && (
            !preg_match('/[a-z]/', $password)
            || !preg_match('/[A-Z]/', $password)
            || !preg_match('/\d/', $password)
        )) {
            $errors['password'][] = 'Use at least one uppercase letter, one lowercase letter, and one number.';
        }

        if ($this->users->findByEmail((string) ($data['email'] ?? ''))) {
            $errors['email'][] = 'An account already exists for this email.';
        }

        if (!$valid || $errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $id = $this->users->create(
                (string) $data['name'],
                (string) $data['email'],
                password_hash($password, PASSWORD_DEFAULT)
            );
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                return ['success' => false, 'errors' => ['email' => ['An account already exists for this email.']]];
            }
            throw $exception;
        }
        $user = $this->users->findById($id);
        Auth::login($user ?: ['id' => $id], false);
        (new ActivityRepository())->record($id, 'account_created', 'Created a BrightCV account');

        return ['success' => true, 'user' => $user];
    }

    public function requestPasswordReset(string $email): void
    {
        $email = mb_strtolower(trim($email));
        if (mb_strlen($email) > 190 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return;
        }
        $user = $this->users->findByEmail($email);
        if (!$user) {
            return;
        }

        $token = bin2hex(random_bytes(32));
        (new PasswordResetRepository())->create($email, $token);
        $link = base_url('/reset-password/' . rawurlencode($token)) . '?email=' . rawurlencode($email);
        $subject = 'Reset your BrightCV password';
        $safeLink = e($link);
        $html = '<p>Hello ' . e($user['name']) . ',</p>'
            . '<p>Use the secure link below to reset your BrightCV password. It expires in 30 minutes.</p>'
            . '<p><a href="' . $safeLink . '">Reset password</a></p>'
            . '<p>If you did not request this, you can ignore the message.</p>';
        $text = "Hello {$user['name']},\n\nReset your password within 30 minutes:\n{$link}\n\n"
            . 'If you did not request this, you can ignore the message.';

        (new MailService())->send($email, $subject, $html, $text);
    }

    public function resetPassword(string $email, string $token, string $password, string $confirmation): array
    {
        $email = mb_strtolower(trim($email));
        if (
            strlen($password) < 8
            || strlen($password) > 128
            || !preg_match('/[A-Z]/', $password)
            || !preg_match('/[a-z]/', $password)
            || !preg_match('/\d/', $password)
        ) {
            return ['success' => false, 'message' => 'Use at least 8 characters with uppercase, lowercase, and a number.'];
        }
        if ($password !== $confirmation) {
            return ['success' => false, 'message' => 'The password confirmation does not match.'];
        }
        if (
            mb_strlen($email) > 190
            || filter_var($email, FILTER_VALIDATE_EMAIL) === false
            || !preg_match('/^[a-f0-9]{64}$/i', $token)
        ) {
            return ['success' => false, 'message' => 'This password reset link is invalid or has expired.'];
        }

        $resets = new PasswordResetRepository();
        $record = $resets->findValid($email, $token);
        if (!$record) {
            return ['success' => false, 'message' => 'This password reset link is invalid or has expired.'];
        }

        $user = $this->users->findByEmail($email);
        if (!$user) {
            return ['success' => false, 'message' => 'This password reset link is invalid or has expired.'];
        }

        try {
            Database::transaction(function () use ($user, $password, $resets, $record): void {
                if (!$resets->markUsed((int) $record['id'])) {
                    throw new InvalidArgumentException('Reset token was already used.');
                }
                $this->users->updatePasswordAndInvalidateSessions(
                    (int) $user['id'],
                    password_hash($password, PASSWORD_DEFAULT)
                );
            });
        } catch (InvalidArgumentException) {
            return ['success' => false, 'message' => 'This password reset link is invalid or has expired.'];
        }
        (new ActivityRepository())->record((int) $user['id'], 'password_reset', 'Reset account password');
        return ['success' => true];
    }

    private function stringValue(mixed $value): string
    {
        return is_string($value) || is_numeric($value) ? (string) $value : '';
    }
}
