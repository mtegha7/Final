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
            Response::error("Unauthorized session", 401);
            return;
        }

        if (!isset($_FILES['id_image']) || !isset($_FILES['selfie'])) {
            Response::error("Both National ID and Live Selfie images are required.");
            return;
        }

        // Define the upload directory (Ensure this matches where Python looks)
        $uploadDir = __DIR__ . '/../../uploads/ids/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Generate unique filenames to prevent overwriting
        $timestamp = time();
        $idName = "id_" . $userId . "_" . $timestamp . ".jpg";
        $selfieName = "selfie_" . $userId . "_" . $timestamp . ".jpg";

        $idPath = $uploadDir . $idName;
        $selfiePath = $uploadDir . $selfieName;

        if (
            move_uploaded_file($_FILES['id_image']['tmp_name'], $idPath) &&
            move_uploaded_file($_FILES['selfie']['tmp_name'], $selfiePath)
        ) {

            try {
                // Call the Service which handles the PythonBridge logic
                // This returns [status, confidence, verification_status]
                $result = $this->faceService->verify($userId, $idName, $selfieName);

                if ($result['status'] === 'success') {
                    Response::success($result, "Verification processed. Status: " . $result['verification_status']);
                } else {
                    Response::error($result['message'] ?? "AI Verification failed.");
                }
            } catch (Throwable $e) {
                Response::error("Service Error: " . $e->getMessage());
            }
        } else {
            Response::error("Failed to save images to the server.");
        }
    }

    /**
     * Endpoint: action=dashboard
     * Aggregates profile, stats, and listings for the agent UI
     */
    public function getDashboard()
    {
        Session::start();
        $userId = Session::get('user_id');

        if (!$userId) {
            Response::error("Unauthorized", 401);
            return;
        }

        try {
            $db = Database::getInstance()->conn;

            // 1. Fetch User details
            $userQuery = "SELECT full_name, email FROM users WHERE id = ? AND role = 'agent'";
            $stmt = $db->prepare($userQuery);
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                Response::error("Agent record not found.", 404);
                return;
            }

            // 2. Fetch Agent Profile (ID paths, Trust Score, Risk Level)
            $profile = $this->agentModel->get($userId);

            // 3. Fetch Recent Listings
            $listingQuery = "SELECT id, title, area_name, price, status, created_at 
                             FROM properties 
                             WHERE agent_id = ? 
                             ORDER BY created_at DESC LIMIT 10";
            $stmt = $db->prepare($listingQuery);
            $stmt->execute([$userId]);
            $listings = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // 4. Calculate Stats
            $statsQuery = "SELECT 
                            COUNT(*) as total_listings, 
                            SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) as approved_listings,
                            SUM(CASE WHEN is_flagged=1 THEN 1 ELSE 0 END) as flagged_listings
                           FROM properties WHERE agent_id = ?";
            $stmt = $db->prepare($statsQuery);
            $stmt->execute([$userId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
                "total_listings" => 0,
                "approved_listings" => 0,
                "flagged_listings" => 0
            ];

            Response::success([
                "user" => $user,
                "profile" => $profile,
                "listings" => $listings,
                "stats" => $stats
            ]);
        } catch (Throwable $e) {
            Response::error("Dashboard Error: " . $e->getMessage());
        }
    }
}
