<?php

declare(strict_types=1);

final class AccountRepository
{
    public function __construct(private readonly UserRepository $users = new UserRepository())
    {
    }

    public function updateProfile(int $userId, array $data): void
    {
        $this->users->updateProfile($userId, $data);
    }

    public function updatePassword(int $userId, string $hash): void
    {
        $this->users->updatePasswordAndInvalidateSessions($userId, $hash);
    }

    public function fullUser(int $userId): ?array
    {
        $public = $this->users->findById($userId);
        return $public ? $this->users->findByEmail((string) $public['email']) : null;
    }
}
