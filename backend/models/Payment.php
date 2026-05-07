<?php

// Payment model requires the database to already be loaded via index.php → config/database.php
// We guard here in case the model is ever instantiated directly.
if (!class_exists('Database')) {
    require_once dirname(__DIR__) . '/config/database.php';
}

class Payment
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->conn;
    }

    /**
     * Insert a new pending payment record.
     * Returns the generated transaction reference string, or false on failure.
     *
     * FIX: Original model had ($propertyId, $clientId, $amount, $method) in the
     * signature but PaymentController was calling it as ($propertyId, $amount, $method)
     * — missing $clientId entirely. Signature and controller are now aligned.
     */
    public function initTransaction($propertyId, $clientId, $amount, $method)
    {
        $ref  = 'TXN-' . strtoupper(uniqid());
        $sql  = "INSERT INTO payments 
                    (property_id, client_id, amount, payment_method, transaction_reference, status) 
                 VALUES (?, ?, ?, ?, ?, 'pending')";

        $stmt = $this->db->prepare($sql);
        if ($stmt->execute([$propertyId, $clientId, $amount, $method, $ref])) {
            return $ref;
        }
        return false;
    }

    /**
     * Update the status of a payment by its reference.
     */
    public function updateStatus($reference, $status)
    {
        $sql = "UPDATE payments SET status = ? WHERE transaction_reference = ?";
        return $this->db->prepare($sql)->execute([$status, $reference]);
    }

    /**
     * Check whether a client already has a completed booking for a property.
     * Prevents duplicate bookings.
     */
    public function hasExistingBooking($propertyId, $clientId)
    {
        $sql  = "SELECT id FROM payments 
                 WHERE property_id = ? AND client_id = ? AND status = 'completed' 
                 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$propertyId, $clientId]);
        return (bool) $stmt->fetch();
    }

    /**
     * Fetch all payments made by a specific client,
     * joined with the property title for display.
     */
    public function getByClient($clientId)
    {
        $sql = "SELECT 
                    p.id,
                    p.transaction_reference,
                    p.property_id,
                    p.amount,
                    p.payment_method,
                    p.status,
                    p.created_at,
                    pr.title AS property_title
                FROM payments p
                LEFT JOIN properties pr ON pr.id = p.property_id
                WHERE p.client_id = ?
                ORDER BY p.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clientId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch all viewing requests for properties belonging to a specific agent,
     * joined with client name/email and property title.
     */
    public function getByAgent($agentId)
    {
        $sql = "SELECT 
                    p.id,
                    p.transaction_reference,
                    p.property_id,
                    p.client_id,
                    p.amount,
                    p.payment_method,
                    p.status,
                    p.created_at,
                    pr.title       AS property_title,
                    u.full_name    AS client_name,
                    u.email        AS client_email
                FROM payments p
                LEFT JOIN properties pr ON pr.id   = p.property_id
                LEFT JOIN users      u  ON u.id    = p.client_id
                WHERE pr.agent_id = ?
                ORDER BY p.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$agentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
