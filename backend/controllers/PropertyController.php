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
            Response::success($data);
        } catch (Throwable $e) {
            Response::error($e->getMessage());
        }
    }

    public function getAllProperties()
    {
        try {
            $data = $this->propertyModel->getAll();

            Response::success($data);
        } catch (Throwable $e) {
            Response::error($e->getMessage(), 500);
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
        if (!Session::get('user_id')) {
            Response::error("Unauthorized", 401);
        }

        try {
            $db = Database::getInstance()->conn;

            $pending = $db->query("SELECT COUNT(*) FROM properties WHERE status = 'pending'")->fetchColumn();
            $flagged = $db->query("SELECT COUNT(*) FROM properties WHERE is_flagged = 1")->fetchColumn();
            $agents = $db->query("SELECT COUNT(*) FROM users WHERE role = 'agent'")->fetchColumn();

            Response::success([
                "pending" => (int)$pending,
                "flagged" => (int)$flagged,
                "agents" => (int)$agents
            ]);
        } catch (Throwable $e) {
            Response::error($e->getMessage());
        }
    }


    public function updateProperty()
    {
        $input = json_decode(file_get_contents("php://input"), true);
        $id = $input['id'] ?? null;

        if (!$id) return Response::error("Missing ID");

        try {
            $db = Database::getInstance()->conn;
            $stmt = $db->prepare("UPDATE properties SET title = ?, price = ? WHERE id = ?");
            $stmt->execute([$input['title'], $input['price'], $id]);
            Response::success([], "Property updated");
        } catch (Throwable $e) {
            Response::error($e->getMessage());
        }
    }


    public function changePropertyStatus()
    {
        $input = json_decode(file_get_contents("php://input"), true);
        $id = $input['id'] ?? null;
        $status = $input['status'] ?? null;

        if (!$id || !$status) {
            return Response::error("Invalid request parameters");
        }

        try {
            // Uses the updateStatus method in your Property model
            $this->propertyModel->updateStatus($id, $status);
            Response::success([], "Status updated to " . $status);
        } catch (Throwable $e) {
            Response::error($e->getMessage());
        }
    }

    public function deleteProperty()
    {
        $input = json_decode(file_get_contents("php://input"), true);
        $id = $input['id'] ?? null;

        try {
            $db = Database::getInstance()->conn;
            $stmt = $db->prepare("DELETE FROM properties WHERE id = ?");
            $stmt->execute([$id]);
            Response::success([], "Property deleted");
        } catch (Throwable $e) {
            Response::error($e->getMessage());
        }
    }
}
