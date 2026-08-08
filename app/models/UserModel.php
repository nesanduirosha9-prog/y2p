<?php
namespace app\models;

class UserModel {
    public function getAllUsers(): array {
        // In a real app this would query a database
        return [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ];
    }
}