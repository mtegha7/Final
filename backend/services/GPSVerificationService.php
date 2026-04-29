<?php

require_once __DIR__ . '/../core/GeoValidator.php';

class GPSVerificationService
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->conn;
    }

    public function validate($lat, $lng, $areaName)
    {
        $stmt = $this->db->prepare("
            SELECT latitude, longitude 
            FROM blantyre_zones 
            WHERE area_name = ?
            LIMIT 1
        ");
        $stmt->execute([$areaName]);
        $zone = $stmt->fetch();

        if (!$zone) {
            return ['valid' => false, 'reason' => 'Unknown area'];
        }

        return GeoValidator::isLocationValid(
            $lat,
            $lng,
            $zone['latitude'],
            $zone['longitude'],
            false // city-level check
        );
    }
}
