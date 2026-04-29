<?php

require_once __DIR__ . '/../controllers/AuthController.php';

$controller = new AuthController();

$method = $_GET['action'] ?? '';

try {

    switch ($method) {

        case 'register':
            $controller->register();
            break;

        case 'login':
            $controller->login();
            break;

        case 'check':
            $controller->check();
            break;

        case 'logout':
            $controller->logout();
            break;

        default:
            Response::error("Invalid auth route", 404);
    }
} catch (Exception $e) {
    Response::error("Server error: " . $e->getMessage(), 500);
}
