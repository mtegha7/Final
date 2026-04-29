<?php

require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Session.php';
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
        }
        //$userId = 1; // for testing. Remove this line in production.

        if (!$userId) {
            Response::error("Unauthorized", 401);
        }

        if (!isset($_FILES['id_image']) || !isset($_FILES['selfie'])) {
            Response::error("Images required");
        }

        $uploadDir = __DIR__ . '/../uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $idPath = $uploadDir . uniqid() . "_id.jpg";
        $selfiePath = $uploadDir . uniqid() . "_selfie.jpg";

        move_uploaded_file($_FILES['id_image']['tmp_name'], $idPath);
        move_uploaded_file($_FILES['selfie']['tmp_name'], $selfiePath);

        // Run AI verification
        $result = $this->faceService->verify($idPath, $selfiePath);

        if ($result['status'] === 'error') {
            Response::error("Verification failed");
        }

        $status = $result['status'];
        $confidence = $result['confidence'];


        // HANDLE STATUS FLOW
        if ($status === "manual_review") {
            // Save as pending review (NOT verified yet)
            $this->agentModel->updateVerificationStatus(
                $userId,
                $idPath,
                $selfiePath,
                $confidence,
                "pending_review"
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
                "verified"
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
                "pending_review"
            );
            Response::success([
                "status" => "pending_review",
                "confidence" => $confidence,
                "message" => "Sent for admin review"
            ]);
        }
    }
}
