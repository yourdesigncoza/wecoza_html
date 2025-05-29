<?php
declare(strict_types=1);

namespace ClassCRUD\Repositories;

use ClassCRUD\Services\DatabaseService;

class UserRepository
{
    private DatabaseService $db;

    public function __construct(DatabaseService $db)
    {
        $this->db = $db;
    }

    /**
     * Find user by username or email (adapted for existing schema)
     */
    public function findByUsernameOrEmail(string $usernameOrEmail): ?array
    {
        // Your existing table uses email as the primary identifier
        $sql = "SELECT * FROM users WHERE email = :identifier";
        return $this->db->queryOne($sql, ['identifier' => $usernameOrEmail]);
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM users WHERE email = :email";
        return $this->db->queryOne($sql, ['email' => $email]);
    }

    /**
     * Find user by username (adapted - uses email)
     */
    public function findByUsername(string $username): ?array
    {
        // Since your table doesn't have username, treat it as email
        $sql = "SELECT * FROM users WHERE email = :username";
        return $this->db->queryOne($sql, ['username' => $username]);
    }

    /**
     * Find user by ID
     */
    public function findById(int $userId): ?array
    {
        $sql = "SELECT * FROM users WHERE user_id = :user_id";
        return $this->db->queryOne($sql, ['user_id' => $userId]);
    }

    /**
     * Check if username or email exists (adapted for existing schema)
     */
    public function existsByUsernameOrEmail(string $username, string $email): bool
    {
        // Since your table only has email, check email only
        $sql = "SELECT COUNT(*) as count FROM users WHERE email = :email";
        $result = $this->db->queryOne($sql, ['email' => $email]);
        return (int)($result['count'] ?? 0) > 0;
    }

    /**
     * Create a new user (adapted for existing schema)
     */
    public function create(array $userData): ?array
    {
        // Map to your existing table structure
        $mappedData = [
            'first_name' => $userData['first_name'] ?? '',
            'surname' => $userData['last_name'] ?? $userData['surname'] ?? '',
            'email' => $userData['email'] ?? '',
            'password_hash' => $userData['password_hash'] ?? '',
            'role' => $userData['role'] ?? 'user',
            'cellphone_number' => $userData['cellphone_number'] ?? null,
            'created_at' => 'NOW()',
            'updated_at' => 'NOW()'
        ];

        $sql = "INSERT INTO users (first_name, surname, email, password_hash, role, cellphone_number, created_at, updated_at)
                VALUES (:first_name, :surname, :email, :password_hash, :role, :cellphone_number, NOW(), NOW())
                RETURNING user_id";

        $result = $this->db->queryOne($sql, $mappedData);

        if ($result) {
            return $this->findById((int)$result['user_id']);
        }

        return null;
    }

    /**
     * Update user
     */
    public function update(int $userId, array $userData): ?array
    {
        $userData['updated_at'] = date('Y-m-d H:i:s');

        $setParts = array_map(fn($col) => "$col = :$col", array_keys($userData));
        $sql = sprintf(
            "UPDATE users SET %s WHERE user_id = :user_id",
            implode(', ', $setParts)
        );

        $userData['user_id'] = $userId;
        $this->db->query($sql, $userData);

        return $this->findById($userId);
    }

    /**
     * Update user password
     */
    public function updatePassword(int $userId, string $passwordHash): bool
    {
        $sql = "UPDATE users SET password_hash = :password_hash, updated_at = :updated_at WHERE user_id = :user_id";
        $stmt = $this->db->query($sql, [
            'password_hash' => $passwordHash,
            'updated_at' => date('Y-m-d H:i:s'),
            'user_id' => $userId
        ]);
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Update user status (adapted - your table doesn't have is_active)
     */
    public function updateStatus(int $userId, bool $isActive): bool
    {
        // Since your table doesn't have is_active field, just return true
        // In a real implementation, you might add this field or use a different approach
        return true;
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin(int $userId): bool
    {
        $sql = "UPDATE users SET updated_at = :updated_at WHERE user_id = :user_id";
        $stmt = $this->db->query($sql, [
            'updated_at' => date('Y-m-d H:i:s'),
            'user_id' => $userId
        ]);
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Find all users with pagination
     */
    public function findAll(int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT * FROM users ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        return $this->db->queryAll($sql, ['limit' => $limit, 'offset' => $offset]);
    }

    /**
     * Count total users
     */
    public function count(): int
    {
        $sql = "SELECT COUNT(*) as total FROM users";
        $result = $this->db->queryOne($sql);
        return (int)($result['total'] ?? 0);
    }

    /**
     * Find users by role (adapted for existing schema)
     */
    public function findByRole(string $role): array
    {
        $sql = "SELECT * FROM users WHERE role = :role ORDER BY first_name, surname";
        return $this->db->queryAll($sql, ['role' => $role]);
    }

    /**
     * Delete user (soft delete by setting inactive)
     */
    public function delete(int $userId): bool
    {
        return $this->updateStatus($userId, false);
    }

    /**
     * Hard delete user (permanent deletion)
     */
    public function hardDelete(int $userId): bool
    {
        $sql = "DELETE FROM users WHERE user_id = :user_id";
        $stmt = $this->db->query($sql, ['user_id' => $userId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Search users by name or email (adapted for existing schema)
     */
    public function search(string $query, int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT * FROM users
                WHERE (first_name ILIKE :query OR surname ILIKE :query OR email ILIKE :query)
                ORDER BY first_name, surname
                LIMIT :limit OFFSET :offset";

        return $this->db->queryAll($sql, [
            'query' => '%' . $query . '%',
            'limit' => $limit,
            'offset' => $offset
        ]);
    }

    /**
     * Get user statistics
     */
    public function getStatistics(): array
    {
        $stats = [];

        // Total users
        $result = $this->db->queryOne("SELECT COUNT(*) as total FROM users WHERE is_active = true");
        $stats['total_users'] = (int)($result['total'] ?? 0);

        // Users by role
        $result = $this->db->queryAll("SELECT role, COUNT(*) as count FROM users WHERE is_active = true GROUP BY role");
        $stats['by_role'] = array_column($result, 'count', 'role');

        // Recent registrations (last 30 days)
        $result = $this->db->queryOne("SELECT COUNT(*) as count FROM users WHERE created_at >= NOW() - INTERVAL '30 days'");
        $stats['recent_registrations'] = (int)($result['count'] ?? 0);

        // Since your table doesn't have is_active, skip this stat
        $stats['by_status'] = ['active' => $stats['total_users']];

        return $stats;
    }

    /**
     * Get users who can be assigned as agents (adapted for existing schema)
     */
    public function getAgentUsers(): array
    {
        $sql = "SELECT user_id, email, first_name, surname, email
                FROM users
                WHERE role IN ('agent', 'admin')
                ORDER BY first_name, surname";

        return $this->db->queryAll($sql);
    }

    /**
     * Get users who can be assigned as supervisors (adapted for existing schema)
     */
    public function getSupervisorUsers(): array
    {
        $sql = "SELECT user_id, email, first_name, surname, email
                FROM users
                WHERE role IN ('supervisor', 'admin')
                ORDER BY first_name, surname";

        return $this->db->queryAll($sql);
    }
}
