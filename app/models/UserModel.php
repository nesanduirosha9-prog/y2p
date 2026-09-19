<?php

namespace app\models;

use app\core\Database;
use PDO;

class UserModel
{
    /**
     * Find a user by their email address
     * 
     * @param string $email
     * @return array|false Returns user data array if found, false otherwise
     */
    public function findByEmail(string $email)
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new user
     * 
     * @param string $email
     * @param string $password The raw password (will be hashed)
     * @return bool True on success, false on failure
     */
    public function create(string $email, string $password): bool
    {
        $pdo = Database::getConnection();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (:email, :password)");
        return $stmt->execute([
            'email' => $email,
            'password' => $hashedPassword
        ]);
    }

    /**
     * Update a user's password
     * 
     * @param string $email
     * @param string $newPassword The raw new password
     * @return bool True on success, false on failure
     */
    public function updatePassword(string $email, string $newPassword): bool
    {
        $pdo = Database::getConnection();
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE email = :email");
        return $stmt->execute([
            'email' => $email,
            'password' => $hashedPassword
        ]);
    }

    /**
     * Get all users
     * 
     * @return array
     */
    public function getAllUsers(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT id, email, created_at FROM users ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}