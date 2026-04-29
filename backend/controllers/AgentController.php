<?php

require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AgentProfile.php';
require_once __DIR__ . '/../services/FaceVerificationService.php';
require_once __DIR__ . '/../services/TrustScoreService.php';

class AgentController
{
    private $agentModel;
    private $faceService;
    private $trustService;

    public function __construct()
    {
        $this->agentModel = new AgentProfile();
        $this->faceService = new FaceVerificationService();
        $this->trustService = new TrustScoreService();
    }

    public function verifyIdentity()
    {
        Session::start();

        $userId = Session::get('user_id');
        if (!$userId) {
            Response::error("Unauthorized", 401);
            return;
        }

        if (!isset($_FILES['id_image']) || !isset($_FILES['selfie'])) {
            Response::error("Images required");
            return;
        }

        $uploadDir = __DIR__ . '/../uploads/ids/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $idPath = $uploadDir . uniqid() . "_id.jpg";
        $selfiePath = __DIR__ . '/../uploads/selfies/' . uniqid() . "_selfie.jpg";

        // Ensure selfies directory exists
        $selfiesDir = __DIR__ . '/../uploads/selfies/';
        if (!file_exists($selfiesDir)) {
            mkdir($selfiesDir, 0777, true);
        }

        // Upload files with error checking
        if (!move_uploaded_file($_FILES['id_image']['tmp_name'], $idPath)) {
            Response::error("Failed to upload ID image");
            return;
        }
        if (!move_uploaded_file($_FILES['selfie']['tmp_name'], $selfiePath)) {
            Response::error("Failed to upload selfie");
            return;
        }

        // Run AI verification
        $result = $this->faceService->verify($userId, $idPath, $selfiePath);

        if ($result['status'] === 'error') {
            Response::error("Verification failed");
            return;
        }

        $status = $result['status'];
        $confidence = $result['confidence'];

        // Determine risk level based on confidence
        $riskLevel = ($confidence > 0.85) ? 'low' : (($confidence > 0.70) ? 'medium' : 'high');

        // HANDLE STATUS FLOW
        if ($status === "manual_review") {
            // Save as pending review (NOT verified yet)
            $this->agentModel->updateVerificationStatus(
                $userId,
                $idPath,
                $selfiePath,
                $confidence,
                "pending_review",
                $riskLevel
            );
            Response::success([
                "status" => "pending_review",
                "confidence" => $confidence,
                "message" => "Sent for admin review"
            ]);
        } elseif ($status === "verified") {
            $this->agentModel->updateVerificationStatus(
                $userId,
                $idPath,
                $selfiePath,
                $confidence,
                "verified",
                "low"
            );
            Response::success([
                "status" => "verified",
                "confidence" => $confidence
            ]);
        } else {
            $this->agentModel->updateVerificationStatus(
                $userId,
                $idPath,
                $selfiePath,
                $confidence,
                "pending_review",
                $riskLevel
            );
            Response::success([
                "status" => "pending_review",
                "confidence" => $confidence,
                "message" => "Sent for admin review"
            ]);
        }
    }

    public function getDashboard()
    {
        Session::start();

        $userId = Session::get('user_id');
        if (!$userId) {
            Response::error("Unauthorized", 401);
            return;
        }

        try {
            // Get user info
            $userQuery = "SELECT id, full_name, email FROM users WHERE id = ? AND role = 'agent'";
            $stmt = Database::getInstance()->conn->prepare($userQuery);
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                Response::error("User not found", 404);
                return;
            }

            // Get agent profile
            $profile = $this->agentModel->get($userId);

            // Get agent's listings
            $listingQuery = "SELECT id, title, area_name, price, status, created_at FROM properties WHERE agent_id = ? ORDER BY created_at DESC LIMIT 10";
            $stmt = Database::getInstance()->conn->prepare($listingQuery);
            $stmt->execute([$userId]);
            $listings = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];

            // Get stats
            $statsQuery = "SELECT COUNT(*) as total_listings, SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) as approved_listings FROM properties WHERE agent_id = ?";
            $stmt = Database::getInstance()->conn->prepare($statsQuery);
            $stmt->execute([$userId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC) ?? [];

            Response::success([
                "user" => $user,
                "profile" => $profile,
                "listings" => $listings,
                "stats" => $stats
            ]);
        } catch (Throwable $e) {
            Response::error($e->getMessage(), 500);
        }
    }
}
