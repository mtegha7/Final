<?php

require_once __DIR__ . '/../controllers/AgentController.php';

$controller = new AgentController();

$action = $_GET['action'] ?? '';

switch ($action) {

    case 'verify':
        $controller->verifyIdentity();
        break;

    default:
        Response::error("Invalid agent route", 404);
}
