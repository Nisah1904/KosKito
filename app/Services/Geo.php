<?php

namespace App\Services;

class Geo
{
    /**
     * Jarak garis lurus (great-circle) dua koordinat dalam kilometer.
     * HANYA jarak garis lurus — bukan jarak rute jalan.
     */
    public static function haversine($lat1, $lng1, $lat2, $lng2): float
    {
        $earthRadius = 6371.0;

        $lat1 = deg2rad((float) $lat1);
        $lng1 = deg2rad((float) $lng1);
        $lat2 = deg2rad((float) $lat2);
        $lng2 = deg2rad((float) $lng2);

        $dLat = $lat2 - $lat1;
        $dLng = $lng2 - $lng1;

        $a = sin($dLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * asin(min(1.0, sqrt($a)));
    }

    public static function formatKm(float $km): string
    {
        if ($km < 1) {
            return number_format($km * 1000, 0, ',', '.') . ' m';
        }

        return number_format($km, 1, ',', '.') . ' km';
    }

    public static function rupiahStatic(int $value): string
    {
        return 'Rp' . number_format($value, 0, ',', '.');
    }
}
