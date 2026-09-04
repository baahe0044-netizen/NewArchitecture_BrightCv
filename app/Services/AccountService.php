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

    /**
     * The claim step for a guest account -- the download gate calls this
     * once, with real name/email/password, rather than creating a second
     * account and having to move the CV across. Validation mirrors
     * AuthService::register() exactly, since this is the same "become a
     * real account" moment, just reached from the builder instead of
     * /register.
     */
    public function claimGuestAccount(int $userId, array $data): array
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
        $password = $data['password'];

        if ($password !== $data['password_confirmation']) {
            $errors['password_confirmation'][] = 'The password confirmation does not match.';
        }

        if ($password !== '' && (
            !preg_match('/[a-z]/', $password)
            || !preg_match('/[A-Z]/', $password)
            || !preg_match('/\d/', $password)
        )) {
            $errors['password'][] = 'Use at least one uppercase letter, one lowercase letter, and one number.';
        }

        $users = new UserRepository();
        // The guest domain is never something a real visitor could type by
        // hand, but reject it explicitly anyway rather than let a claim
        // silently collide with how guest rows are told apart from real ones.
        if (str_ends_with($data['email'], '@guest.brightcv.internal')) {
            $errors['email'][] = 'Enter a real email address.';
        } elseif ($users->findByEmail($data['email'])) {
            $errors['email'][] = 'An account already exists for this email.';
        }

        if (!$valid || $errors !== []) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $claimed = $this->accounts->claimGuest(
                $userId,
                $data['name'],
                $data['email'],
                password_hash($password, PASSWORD_DEFAULT)
            );
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                return ['success' => false, 'errors' => ['email' => ['An account already exists for this email.']]];
            }
            throw $exception;
        }

        if (!$claimed) {
            return ['success' => false, 'errors' => ['email' => ['This account has already been set up. Refresh and sign in instead.']]];
        }

        $user = $users->findById($userId);
        (new ActivityRepository())->record($userId, 'account_created', 'Created a BrightCV account');
        return ['success' => true, 'user' => $user];
    }

    private function stringValue(mixed $value): string
    {
        return is_string($value) || is_numeric($value) ? (string) $value : '';
    }
}
