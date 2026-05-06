<?php
require_once __DIR__ . '/../models/Payment.php';

class PaymentController
{
    public function processSimulation()
    {
        $input = json_decode(file_get_contents("php://input"), true);

        $propertyId = $input['property_id'];
        $method = $input['method']; // e.g., 'mpamba'
        $clientId = $_SESSION['user_id'] ?? 1; // Fallback for testing

        $payment = new Payment();
        $ref = $payment->initTransaction($propertyId, 150000, $method); // Mock price

        // SIMULATION LOGIC:
        // 1. Pretend to wait for a gateway response
        usleep(1500000); // 1.5 second delay

        // 2. Randomly decide if payment succeeds (90% success rate)
        $isSuccessful = (rand(1, 100) <= 90);

        if ($isSuccessful) {
            $payment->updateStatus($ref, 'completed');
            Response::success(['reference' => $ref], "Payment Successful via " . ucfirst($method));
        } else {
            $payment->updateStatus($ref, 'failed');
            Response::error("Payment failed: Insufficient funds or timeout.", 402);
        }
    }
}
