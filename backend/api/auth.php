<?php

require_once __DIR__ . '/../controllers/AuthController.php';
// FIX: the default switch branch calls Response::error() but Response was
// never loaded in this file. Any unknown action would produce a fatal
// "Class 'Response' not found" error instead of a clean 404 JSON response.
require_once __DIR__ . '/../core/Response.php';

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
