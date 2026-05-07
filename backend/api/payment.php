<?php

require_once __DIR__ . '/../controllers/PaymentController.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Session.php';

Session::start();

// All payment routes require authentication
if (!Session::get('user_id')) {
    Response::error("Authentication required", 401);
    exit;
}

$controller = new PaymentController();
$action = $_GET['action'] ?? '';

try {
    switch ($action) {

        // Client initiates a payment / booking
        case 'initiate':
            $controller->processSimulation();
            break;

        // Client fetches their own bookings
        case 'my_bookings':
            $controller->getClientBookings();
            break;

        // Agent fetches viewing requests on their properties
        case 'agent_viewings':
            if (Session::get('role') !== 'agent') {
                Response::error("Access denied", 403);
            }
            $controller->getAgentViewings();
            break;

        default:
            Response::error("Invalid payment route", 404);
    }
} catch (Exception $e) {
    Response::error("Server error: " . $e->getMessage(), 500);
}
