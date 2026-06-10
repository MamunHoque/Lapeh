<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MetaController extends Controller
{
    /**
     * Public reference data for the apps: predefined rating tags and
     * complaint types with localized (en/ar) labels.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'rating_tags' => config('lapeh.rating_tags'),
            'complaint_types' => config('lapeh.complaint_types'),
        ]);
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
