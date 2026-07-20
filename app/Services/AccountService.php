<?php

declare(strict_types=1);

final class AccountService
{
    public function __construct(private readonly AccountRepository $accounts = new AccountRepository())
    {
    }

    public function updateProfile(int $userId, array $data): array
    {
        $data = [
            'name' => trim(strip_tags($this->stringValue($data['name'] ?? ''))),
            'job_title' => trim(strip_tags($this->stringValue($data['job_title'] ?? ''))),
            'locale' => $this->stringValue($data['locale'] ?? ''),
        ];
        $validator = new Validator();
        if (!$validator->validate($data, [
            'name' => 'required|min:2|max:100',
            'job_title' => 'nullable|max:120',
            'locale' => 'required|in:en,fr,es',
        ])) {
            return ['success' => false, 'errors' => $validator->errors()];
        }

        $this->accounts->updateProfile($userId, $data);
        (new ActivityRepository())->record($userId, 'profile_updated', 'Updated account profile');
        return ['success' => true];
    }

    public function updatePassword(int $userId, string $current, string $password, string $confirmation): array
    {
        $user = $this->accounts->fullUser($userId);
        if (!$user || strlen($current) > 128 || !password_verify($current, (string) $user['password_hash'])) {
            return ['success' => false, 'message' => 'Your current password is incorrect.'];
        }
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
            return ['success' => false, 'message' => 'The new password confirmation does not match.'];
        }

        $this->accounts->updatePassword($userId, password_hash($password, PASSWORD_DEFAULT));
        $updatedUser = $this->accounts->fullUser($userId);
        Session::put('auth_version', (int) ($updatedUser['auth_version'] ?? ((int) $user['auth_version'] + 1)));
        Session::regenerate();
        Session::forget('_csrf_token');
        (new ActivityRepository())->record($userId, 'password_updated', 'Changed account password');
        return ['success' => true];
    }

    private function stringValue(mixed $value): string
    {
        return is_string($value) || is_numeric($value) ? (string) $value : '';
    }
}
