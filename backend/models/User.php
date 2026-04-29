<?php

require_once __DIR__ . '/../config/database.php';

class User
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->conn;
    }


    // REGISTER
    public function register($name, $email, $password, $role)
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $this->db->prepare(
            "INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)"
        );

        if ($stmt->execute([$name, $email, $hash, $role])) {
            return $this->db->lastInsertId();
        }
        return false;
    }


    // LOGIN
    public function authenticate($email, $password)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);

        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }


    // CHECK IF EMAIL EXISTS 
    public function exists($email)
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        return $stmt->fetch() ? true : false;
    }
}
