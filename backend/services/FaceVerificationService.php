<?php

require_once __DIR__ . '/../core/PythonBridge.php';
require_once __DIR__ . '/../models/AgentProfile.php';
require_once __DIR__ . '/../models/FraudLog.php';

class FaceVerificationService
{
    private $agentModel;
    private $fraudModel;

    public function __construct()
    {
        $this->agentModel = new AgentProfile();
        $this->fraudModel = new FraudLog();
    }

    public function verify($userId, $idPath, $selfiePath)
    {
        // Call Python AI
        $result = PythonBridge::run('face_verify.py', [
            $idPath,
            $selfiePath
        ]);

        if (!$result || isset($result['error'])) {
            return [
                "status" => "error",
                "message" => "Face verification failed"
            ];
        }

        $confidence = floatval($result['confidence']);

        // NEW DECISION LOGIC (SAFE + REALISTIC)

        if ($confidence >= 85) {
            $status = "verified";
            $risk = "low";
        } elseif ($confidence >= 40) {
            $status = "pending_review";
            $risk = "medium";
        } else {
            $status = "pending_review";
            $risk = "high";

            $this->fraudModel->logIdentityRisk(
                $userId,
                "Very low confidence: {$confidence}%"
            );
        }

        // Save everything
        $this->agentModel->updateVerificationStatus(
            $userId,
            $idPath,
            $selfiePath,
            $confidence,
            $status,
            $risk // 👈 new field (we'll add it)
        );

        return [
            "status" => $status,
            "confidence" => $confidence,
            "risk_level" => $risk
        ];
    }
}
