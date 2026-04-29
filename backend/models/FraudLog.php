<?php

class FraudLog
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->conn;
    }

    public function create($propertyId, $agentId, $type, $message)
    {
        $sql = "INSERT INTO fraud_logs (property_id, agent_id, type, message)
                VALUES (?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $propertyId,
            $agentId,
            $type,
            $message
        ]);
    }

    public function getAll()
    {
        $sql = "SELECT * FROM fraud_logs ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function logIdentityRisk($agentId, $message)
    {
        return $this->create(
            null,
            $agentId,
            'identity',
            $message
        );
    }
}
