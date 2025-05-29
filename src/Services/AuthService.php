<?php
declare(strict_types=1);

namespace ClassCRUD\Services;

use ClassCRUD\Repositories\UserRepository;

class AuthService
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Authenticate user with username/email and password
     */
    public function authenticate(string $username, string $password): ?array
    {
        // Find user by username or email
        $user = $this->userRepository->findByUsernameOrEmail($username);
        
        if (!$user) {
            return null;
        }

        // Your existing table doesn't have is_active field, so skip this check
        // All users in your table are considered active

        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }

        // Remove password hash from returned data
        unset($user['password_hash']);
        
        // Update last login timestamp
        $this->userRepository->updateLastLogin($user['user_id']);

        return $user;
    }

    /**
     * Create a new user
     */
    public function createUser(array $userData): ?array
    {
        // Hash the password
        $userData['password_hash'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        unset($userData['password']);

        // Set default values
        $userData['role'] = $userData['role'] ?? 'user';

        return $this->userRepository->create($userData);
    }

    /**
     * Check if username or email already exists
     */
    public function userExists(string $username, string $email): bool
    {
        return $this->userRepository->existsByUsernameOrEmail($username, $email);
    }

    /**
     * Get user by ID
     */
    public function getUserById(int $userId): ?array
    {
        $user = $this->userRepository->findById($userId);
        
        if ($user) {
            // Remove password hash from returned data
            unset($user['password_hash']);
        }

        return $user;
    }

    /**
     * Update user profile
     */
    public function updateUser(int $userId, array $userData): ?array
    {
        // If password is being updated, hash it
        if (isset($userData['password'])) {
            $userData['password_hash'] = password_hash($userData['password'], PASSWORD_DEFAULT);
            unset($userData['password']);
        }

        return $this->userRepository->update($userId, $userData);
    }

    /**
     * Change user password
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        $user = $this->userRepository->findById($userId);
        
        if (!$user) {
            return false;
        }

        // Verify current password
        if (!password_verify($currentPassword, $user['password_hash'])) {
            return false;
        }

        // Update with new password
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        return $this->userRepository->updatePassword($userId, $newPasswordHash);
    }

    /**
     * Deactivate user account
     */
    public function deactivateUser(int $userId): bool
    {
        return $this->userRepository->updateStatus($userId, false);
    }

    /**
     * Activate user account
     */
    public function activateUser(int $userId): bool
    {
        return $this->userRepository->updateStatus($userId, true);
    }

    /**
     * Get all users (for admin)
     */
    public function getAllUsers(int $limit = 50, int $offset = 0): array
    {
        $users = $this->userRepository->findAll($limit, $offset);
        
        // Remove password hashes from all users
        return array_map(function ($user) {
            unset($user['password_hash']);
            return $user;
        }, $users);
    }

    /**
     * Get user count
     */
    public function getUserCount(): int
    {
        return $this->userRepository->count();
    }

    /**
     * Generate password reset token (for future implementation)
     */
    public function generatePasswordResetToken(string $email): ?string
    {
        $user = $this->userRepository->findByEmail($email);
        
        if (!$user) {
            return null;
        }

        // Generate a secure token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Store token in database (you'd need to add a password_reset_tokens table)
        // For now, just return the token
        return $token;
    }

    /**
     * Validate user role
     */
    public function hasRole(int $userId, string $role): bool
    {
        $user = $this->getUserById($userId);
        return $user && $user['role'] === $role;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(int $userId): bool
    {
        return $this->hasRole($userId, 'admin');
    }

    /**
     * Get users by role
     */
    public function getUsersByRole(string $role): array
    {
        $users = $this->userRepository->findByRole($role);
        
        // Remove password hashes
        return array_map(function ($user) {
            unset($user['password_hash']);
            return $user;
        }, $users);
    }
}
