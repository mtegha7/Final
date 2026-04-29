<?php

require_once __DIR__ . '/../controllers/AdminController.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Session.php';

$controller = new AdminController();

// Get the action from the URL (e.g., ?route=admin&action=listings)
$action = $_GET['action'] ?? '';

// Security Check: Only allow logged-in admins
//Session::start();
//if (Session::get('role') !== 'admin') {
//  Response::error("Unauthorized: Admin access required", 403);
//}

try {
    switch ($action) {
        case 'listings':
            // Fetches all listings (including flagged/pending) for admin review
            $controller->getListings();
            break;

        case 'approve_listing':
            // Logic to set property status to 'approved'
            $controller->approveListing();
            break;

        case 'reject_listing':
            // Logic to set property status to 'rejected' or delete
            $controller->rejectListing();
            break;

        case 'users':
            // Logic to fetch all system users
            $controller->getUsers();
            break;

        case 'fraud_logs':
            // Logic to fetch detected fraud flags
            $controller->getFraudLogs();
            break;

        case 'stats':
            // Fetch admin dashboard statistics
            $controller->getStats();
            break;

        default:
            Response::error("Invalid admin action: " . $action, 404);
            break;
    }
} catch (Exception $e) {
    Response::error("Admin API Error: " . $e->getMessage(), 500);
}
