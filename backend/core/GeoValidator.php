<?php

class GeoValidator
{
    const COMPOUND_THRESHOLD = 30;   // meters
    const DUPLICATE_THRESHOLD = 25;  // meters (stricter than compound logic)

    const MAX_GPS_ACCURACY = 50;     // meters (reject noisy GPS)

    public static function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * 1. GPS quality gate (anti-fake / low accuracy rejection)
     */
    public static function isGpsAccurate($accuracy)
    {
        return $accuracy <= self::MAX_GPS_ACCURACY;
    }

    /**
     * 2. Basic proximity check
     */
    public static function isWithinThreshold($lat1, $lon1, $lat2, $lon2, $threshold)
    {
        $distance = self::calculateDistance($lat1, $lon1, $lat2, $lon2);

        return [
            'valid' => $distance <= $threshold,
            'distance_meters' => round($distance, 2),
            'threshold' => $threshold
        ];
    }

    /**
     * 3. Duplicate detection helper
     */
    public static function isDuplicateLocation($lat, $lon, $existingPoints)
    {
        foreach ($existingPoints as $point) {
            $distance = self::calculateDistance(
                $lat,
                $lon,
                $point['latitude'],
                $point['longitude']
            );

            if ($distance <= self::DUPLICATE_THRESHOLD) {
                return [
                    'duplicate' => true,
                    'matched_property_id' => $point['id'],
                    'distance' => round($distance, 2)
                ];
            }
        }

        return ['duplicate' => false];
    }

    public static function isLocationValid(
        $agentLat,
        $agentLon,
        $targetLat,
        $targetLon,
        $strict = false
    ) {
        $threshold = $strict
            ? self::COMPOUND_THRESHOLD
            : self::COMPOUND_THRESHOLD; // you can change later if needed

        $result = self::isWithinThreshold(
            $agentLat,
            $agentLon,
            $targetLat,
            $targetLon,
            $threshold
        );

        return [
            'valid' => $result['valid'],
            'distance_meters' => $result['distance_meters'],
            'threshold_used' => $threshold
        ];
    }
}
