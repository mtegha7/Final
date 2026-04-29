<?php

require_once __DIR__ . '/../models/FraudLog.php';
require_once __DIR__ . '/../services/PriceAnalysisService.php';
require_once __DIR__ . '/../services/GPSVerificationService.php';

class FraudDetectionService
{
    private $fraudLog;
    private $priceService;
    private $gpsService;

    public function __construct()
    {
        $this->fraudLog = new FraudLog();
        $this->priceService = new PriceAnalysisService();
        $this->gpsService = new GPSVerificationService();
    }

    public function analyze($propertyData)
    {
        $flags = [];

        /* 1. PRICE CHECK */
        $priceCheck = $this->priceService->check(
            $propertyData['area_name'],
            $propertyData['property_type'],
            $propertyData['price']
        );

        if ($priceCheck['flag']) {
            $message = "Price suspicious: {$priceCheck['deviation']}% below market";

            $this->fraudLog->create(
                $propertyData['property_id'],
                $propertyData['agent_id'],
                'price',
                $message
            );

            $flags[] = 'price';
        }

        /* 2. GPS CHECK    */
        $gpsCheck = $this->gpsService->validate(
            $propertyData['latitude'],
            $propertyData['longitude'],
            $propertyData['area_name']
        );

        if (!$gpsCheck['valid']) {
            $message = "Location mismatch: {$gpsCheck['distance_meters']}m away from {$propertyData['area_name']}";

            $this->fraudLog->create(
                $propertyData['property_id'],
                $propertyData['agent_id'],
                'gps',
                $message
            );

            $flags[] = 'gps';
        }

        /* FINAL DECISION */

        if (!empty($flags)) {
            return [
                'status' => 'flagged',
                'issues' => $flags
            ];
        }

        return [
            'status' => 'clean'
        ];
    }
}
