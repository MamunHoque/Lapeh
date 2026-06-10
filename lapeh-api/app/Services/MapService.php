<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MapService
{
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.google_maps.key', '');
    }

    public function distanceKm(float $fromLat, float $fromLng, float $toLat, float $toLng): float
    {
        if ($this->apiKey) {
            try {
                $response = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json', [
                    'origins' => "{$fromLat},{$fromLng}",
                    'destinations' => "{$toLat},{$toLng}",
                    'units' => 'metric',
                    'key' => $this->apiKey,
                ]);

                $data = $response->json();
                if ($data['status'] === 'OK') {
                    $meters = $data['rows'][0]['elements'][0]['distance']['value'] ?? null;
                    if ($meters !== null) {
                        return round($meters / 1000, 2);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Google Maps API error, falling back to Haversine', ['error' => $e->getMessage()]);
            }
        }

        return $this->haversine($fromLat, $fromLng, $toLat, $toLng);
    }

    public function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return round($R * $c, 2);
    }

    public function reverseGeocode(float $lat, float $lng): string
    {
        if ($this->apiKey) {
            try {
                $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'latlng' => "{$lat},{$lng}",
                    'key' => $this->apiKey,
                ]);
                $data = $response->json();
                if ($data['status'] === 'OK' && !empty($data['results'])) {
                    return $data['results'][0]['formatted_address'];
                }
            } catch (\Throwable $e) {
                Log::warning('Geocoding error', ['error' => $e->getMessage()]);
            }
        }
        return "{$lat}, {$lng}";
    }
}
