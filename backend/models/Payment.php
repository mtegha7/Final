<?php
class Payment
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->conn;
    }

    public function initTransaction($propertyId, $clientId, $amount, $method)
    {
        $ref = 'TXN-' . strtoupper(uniqid());
        $sql = "INSERT INTO payments (property_id, client_id, amount, payment_method, transaction_reference, status) 
                VALUES (?, ?, ?, ?, ?, 'pending')";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$propertyId, $clientId, $amount, $method, $ref]);
        return $ref;
    }

    public function updateStatus($reference, $status)
    {
        $sql = "UPDATE payments SET status = ? WHERE transaction_reference = ?";
        return $this->db->prepare($sql)->execute([$status, $reference]);
    }
}
