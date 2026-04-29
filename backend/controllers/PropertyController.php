<?php

require_once __DIR__ . '/../models/Property.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../config/database.php';

class PropertyController
{
    private $propertyModel;

    public function __construct()
    {
        $this->propertyModel = new Property();
    }

    public function getApprovedProperties()
    {
        try {
            $data = $this->propertyModel->getAllApproved();

            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
        } catch (Throwable $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function createProperty()
    {
        Session::start();
        
        $userId = Session::get('user_id');
        if (!$userId) {
            Response::error("Unauthorized", 401);
        }

        try {
            $data = [
                'agent_id' => $userId,
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'price' => $_POST['price'] ?? 0,
                'property_type' => $_POST['property_type'] ?? '',
                'area_name' => $_POST['area_name'] ?? '',
                'latitude' => $_POST['latitude'] ?? null,
                'longitude' => $_POST['longitude'] ?? null,
                'status' => 'pending'
            ];

            if (!$data['title'] || !$data['price']) {
                Response::error("Title and price are required");
            }

            if ($this->propertyModel->create($data)) {
                Response::success([
                    "message" => "Property created successfully",
                    "property" => $data
                ]);
            } else {
                Response::error("Failed to create property");
            }
        } catch (Throwable $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    public function getAdminStats()
    {
        Session::start();
        
        $userId = Session::get('user_id');
        if (!$userId) {
            Response::error("Unauthorized", 401);
        }

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
