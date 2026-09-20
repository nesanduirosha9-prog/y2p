<?php
require_once __DIR__ . '/../bootstrap.php';
use app\models\UserModel;

$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
$test1 = password_verify('password', $hash) ? 'yes' : 'no';
$test2 = password_verify('password123', $hash) ? 'yes' : 'no';

$pdo = \app\core\Database::getConnection();
$stmt = $pdo->query("SELECT * FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Hash matches 'password': $test1\n";
echo "Hash matches 'password123': $test2\n";
echo "Users in DB:\n";
print_r($users);
