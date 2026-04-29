<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Property.php';
require_once __DIR__ . '/../models/FraudLog.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../config/database.php';

class AdminController
{
    private $userModel;
    private $propertyModel;
    private $fraudModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->propertyModel = new Property();
        $this->fraudModel = new FraudLog();
    }

    public function getUsers()
    {
        Response::success($this->userModel->getAll());
    }

    public function getListings()
    {
        Response::success($this->propertyModel->getAll());
    }

    public function getFraudLogs()
    {
        Response::success($this->fraudModel->getAll());
    }

    public function approveListing()
    {
        $input = json_decode(file_get_contents("php://input"), true);
        $id = $input['property_id'] ?? null;
        if ($id && $this->propertyModel->updateStatus($id, 'approved')) {
            Response::success([], "Property approved");
        } else {
            Response::error("Failed to approve property");
        }
    }

    public function rejectListing()
    {
        $input = json_decode(file_get_contents("php://input"), true);
        $id = $input['property_id'] ?? null;
        if ($id && $this->propertyModel->updateStatus($id, 'rejected')) {
            Response::success([], "Property rejected");
        } else {
            Response::error("Failed to reject property");
        }
    }

    public function getStats()
    {
        try {
            $db = Database::getInstance()->conn;

            // Pending verifications
            $stmt = $db->query("SELECT COUNT(*) as count FROM properties WHERE status = 'pending'");
            $pending = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Flagged listings
            $stmt = $db->query("SELECT COUNT(*) as count FROM properties WHERE is_flagged = 1");
            $flagged = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            // Active agents
            $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'agent'");
            $agents = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            Response::success([
                "pending" => $pending,
                "flagged" => $flagged,
                "agents" => $agents
            ]);
        } catch (Throwable $e) {
            Response::error($e->getMessage(), 500);
        }
    }
}
