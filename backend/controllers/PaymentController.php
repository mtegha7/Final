<?php

require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/Session.php';

class PaymentController
{
    private $paymentModel;

    public function __construct()
    {
        $this->paymentModel = new Payment();
    }

    /**
     * Initiate and simulate a payment for a property viewing.
     * Called by clients via POST payment/initiate
     */
    public function processSimulation()
    {
        $input = json_decode(file_get_contents("php://input"), true);

        $propertyId = $input['property_id'] ?? null;
        $method     = $input['method'] ?? 'card';
        $clientId   = Session::get('user_id');

        if (!$propertyId) {
            Response::error("Property ID is required", 400);
            return;
        }

        $validMethods = ['card', 'mpamba', 'airtel_money'];
        if (!in_array($method, $validMethods)) {
            Response::error("Invalid payment method", 400);
            return;
        }

        // Check if client already has a completed booking for this property
        if ($this->paymentModel->hasExistingBooking($propertyId, $clientId)) {
            Response::error("You have already booked a viewing for this property.", 409);
            return;
        }

        // Fixed viewing fee: MWK 10,000
        $amount = 10000;

        // FIX: Original PaymentController passed args in wrong order.
        // Payment::initTransaction signature is ($propertyId, $clientId, $amount, $method)
        $ref = $this->paymentModel->initTransaction($propertyId, $clientId, $amount, $method);

        if (!$ref) {
            Response::error("Failed to initiate payment", 500);
            return;
        }

        // Simulate gateway delay (1.5 seconds)
        usleep(1500000);

        // Simulate 90% success rate
        $isSuccessful = (rand(1, 100) <= 90);

        if ($isSuccessful) {
            $this->paymentModel->updateStatus($ref, 'completed');
            Response::success(
                ['reference' => $ref],
                "Payment successful via " . ucfirst(str_replace('_', ' ', $method))
            );
        } else {
            $this->paymentModel->updateStatus($ref, 'failed');
            Response::error("Payment failed: Insufficient funds or gateway timeout. Please try again.", 402);
        }
    }

    /**
     * Return all bookings for the logged-in client.
     * Called by clients via GET payment/my_bookings
     */
    public function getClientBookings()
    {
        $clientId = Session::get('user_id');
        $bookings = $this->paymentModel->getByClient($clientId);
        Response::success($bookings);
    }

    /**
     * Return all viewing requests on the logged-in agent's properties.
     * Called by agents via GET payment/agent_viewings
     */
    public function getAgentViewings()
    {
        $agentId  = Session::get('user_id');
        $viewings = $this->paymentModel->getByAgent($agentId);
        Response::success($viewings);
    }
}
