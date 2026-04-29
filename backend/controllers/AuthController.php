<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../helpers/validator.php';

class AuthController
{

    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }


    // REGISTER
    public function register()
    {

        $input = json_decode(file_get_contents("php://input"), true);

        $name = $input['full_name'] ?? '';
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        $role = $input['role'] ?? 'client';

        // Basic validation
        if (!$name || !$email || !$password) {
            Response::error("All fields are required", 400);
        }

        // Password validation using helper
        $passwordErrors = Validator::validatePassword($password);
        if (!empty($passwordErrors)) {
            Response::error($passwordErrors, 400);
        }

        // Check if user exists
        if ($this->userModel->exists($email)) {
            Response::error("Email already exists", 409);
        }

        $userId = $this->userModel->register($name, $email, $password, $role);

        if (!$userId) {
            Response::error("Registration failed", 500);
        }

        Response::success([
            "user_id" => $userId
        ], "User registered successfully");
    }


    // LOGIN
    public function login()
    {

        $input = json_decode(file_get_contents("php://input"), true);

        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';

        if (!$email || !$password) {
            Response::error("Email and password required", 400);
        }

        $user = $this->userModel->authenticate($email, $password);

        if (!$user) {
            Response::error("Invalid credentials", 401);
        }

        Session::start();
        Session::set('user_id', $user['id']);
        Session::set('role', $user['role']);

        Response::success([
            "user_id" => $user['id'],
            "role" => $user['role']
        ], "Login successful");
    }


    // CHECK AUTH
    public function check()
    {

        Session::start();

        if (!Session::get('user_id')) {
            Response::error("Not authenticated", 401);
        }

        Response::success([
            "user_id" => Session::get('user_id'),
            "role" => Session::get('role')
        ]);
    }


    // LOGOUT
    public function logout()
    {
        Session::start();
        Session::destroy();

        Response::success([], "Logged out");
    }
}
