<?php
class Property
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->conn;
    }

    public function create($data)
    {
        $sql = "INSERT INTO properties 
                (agent_id, title, description, price, property_type, area_name, latitude, longitude, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $data['agent_id'],
            $data['title'],
            $data['description'],
            $data['price'],
            $data['property_type'],
            $data['area_name'],
            $data['latitude'],
            $data['longitude'],
            $data['status'] ?? 'pending'
        ]);

        return $this->db->lastInsertId();
    }

    public function getAllApproved()
    {
        $sql = "SELECT p.*, u.full_name, ap.trust_score
                FROM properties p
                JOIN users u ON p.agent_id = u.id
                JOIN agent_profiles ap ON u.id = ap.user_id
                WHERE p.status = 'approved'
                ORDER BY p.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
