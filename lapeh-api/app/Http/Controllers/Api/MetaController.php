<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MapService;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MetaController extends Controller
{
    /**
     * Public reference data for the apps: predefined rating tags and
     * complaint types with localized (en/ar) labels, plus runtime app config
     * (branding, maps client key, registration flags, support/FAQ) so the
     * clients never need to hardcode these.
     */
    public function index(SettingsService $settings): JsonResponse
    {
        $config = $settings->publicConfig();

        return response()->json([
            'rating_tags' => config('lapeh.rating_tags'),
            'complaint_types' => config('lapeh.complaint_types'),
            // Admin-editable support (settings store) with config/lapeh.php fallback.
            'support' => $config['support'],
            'app_config' => $config,
        ]);
    }

    /** Lighter endpoint for clients that only need runtime app config. */
    public function appConfig(SettingsService $settings): JsonResponse
    {
        return response()->json($settings->publicConfig());
    }

    /**
     * Reverse-geocode coordinates server-side. The Google Geocoding REST API
     * has no CORS headers, so calling it from Flutter web fails; proxying it
     * here works on every platform and reuses the server Maps key.
     */
    public function reverseGeocode(Request $request, MapService $map): JsonResponse
    {
        $data = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        return response()->json([
            'address' => $map->reverseGeocode((float) $data['lat'], (float) $data['lng']),
        ]);
    }
}
