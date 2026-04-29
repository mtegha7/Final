<?php

require_once __DIR__ . '/../config/database.php';

class AgentProfile
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->conn;
    }

    public function get($userId)
    {
        $stmt = $this->db->prepare("
            SELECT u.full_name, u.email, ap.*
            FROM users u
            LEFT JOIN agent_profiles ap ON u.id = ap.user_id
            WHERE u.id = ?
        ");

        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    public function updateVerificationStatus($userId, $idImage, $selfieImage, $confidence, $status, $risk)
    {
        $sql = "INSERT INTO agent_profiles 
            (user_id, national_id_path, selfie_path, verification_confidence, verification_status, trust_score, is_verified, risk_level) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            national_id_path = VALUES(national_id_path),
            selfie_path = VALUES(selfie_path),
            verification_confidence = VALUES(verification_confidence),
            verification_status = VALUES(verification_status),
            risk_level = VALUES(risk_level),
            trust_score = VALUES(trust_score),
            is_verified = VALUES(is_verified)";

        $isVerified = ($status === 'verified') ? 1 : 0;
        $score = ($status === 'verified') ? 8.5 : 0;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $userId,
            $idImage,
            $selfieImage,
            $confidence,
            $status,
            $score,
            $isVerified,
            $risk
        ]);
    }
}
