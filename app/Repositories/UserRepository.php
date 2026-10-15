<?php

declare(strict_types=1);

final class UserRepository
{
    private readonly PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findById(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT id, name, email, role, plan, locale, job_title, avatar_path, is_guest, email_verified_at,
                    auth_version, created_at
             FROM users WHERE id = ? LIMIT 1'
        );
        $statement->execute([$id]);
        return $statement->fetch() ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $statement->execute([mb_strtolower(trim($email))]);
        return $statement->fetch() ?: null;
    }

    public function create(string $name, string $email, string $passwordHash): int
    {
        $statement = $this->db->prepare(
            "INSERT INTO users (name, email, password_hash, role, plan, locale, created_at, updated_at)
             VALUES (?, ?, ?, 'user', 'free', 'en', NOW(), NOW())"
        );
        $statement->execute([trim($name), mb_strtolower(trim($email)), $passwordHash]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * A CV started before any account exists still needs a real user_id --
     * every existing feature (autosave, ATS, gamification, dashboard) is
     * scoped to one. A synthetic, unguessable email and password satisfy the
     * NOT NULL/UNIQUE columns without either ever being shown or usable to
     * sign in; claimGuest() below fills in the real values in place.
     */
    public function createGuest(): int
    {
        $email = 'guest_' . bin2hex(random_bytes(16)) . '@guest.brightcv.internal';
        $hash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
        $statement = $this->db->prepare(
            "INSERT INTO users (name, email, password_hash, role, plan, locale, is_guest, created_at, updated_at)
             VALUES ('Guest', ?, ?, 'user', 'free', 'en', 1, NOW(), NOW())"
        );
        $statement->execute([$email, $hash]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Turns a guest row into a real account in place -- same id, same
     * resumes, same gamification history -- rather than creating a new
     * account and migrating data across. Returns false if the row was
     * already claimed (or never existed), so the caller can tell "someone
     * already set this account up" apart from a genuine database error.
     */
    public function claimGuest(int $id, string $name, string $email, string $passwordHash): bool
    {
        $statement = $this->db->prepare(
            'UPDATE users
             SET name = ?, email = ?, password_hash = ?, is_guest = 0, updated_at = NOW()
             WHERE id = ? AND is_guest = 1'
        );
        $statement->execute([trim($name), mb_strtolower(trim($email)), $passwordHash, $id]);
        return $statement->rowCount() > 0;
    }

    /**
     * The other ending for a guest row: they already have an account and
     * logged into it instead of claiming this one. Its resumes and activity
     * have already been moved to that real account by this point, so the
     * row is empty and would otherwise sit unclaimed forever -- the
     * is_guest = 1 guard means this can never remove a real account, even
     * called with a wrong id.
     */
    public function deleteGuest(int $id): bool
    {
        $statement = $this->db->prepare('DELETE FROM users WHERE id = ? AND is_guest = 1');
        $statement->execute([$id]);
        return $statement->rowCount() > 0;
    }

    public function updateLastLogin(int $id): void
    {
        $statement = $this->db->prepare('UPDATE users SET last_login_at = NOW(), updated_at = NOW() WHERE id = ?');
        $statement->execute([$id]);
    }

    public function updatePasswordHash(int $id, string $hash): void
    {
        $statement = $this->db->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?');
        $statement->execute([$hash, $id]);
    }

    public function updatePasswordAndInvalidateSessions(int $id, string $hash): void
    {
        $statement = $this->db->prepare(
            'UPDATE users
             SET password_hash = ?, auth_version = auth_version + 1, updated_at = NOW()
             WHERE id = ?'
        );
        $statement->execute([$hash, $id]);
    }

    public function updateProfile(int $id, array $data): void
    {
        $statement = $this->db->prepare(
            'UPDATE users SET name = ?, job_title = ?, locale = ?, updated_at = NOW() WHERE id = ?'
        );
        $statement->execute([
            trim((string) ($data['name'] ?? '')),
            trim((string) ($data['job_title'] ?? '')),
            (string) ($data['locale'] ?? 'en'),
            $id,
        ]);
    }
}
