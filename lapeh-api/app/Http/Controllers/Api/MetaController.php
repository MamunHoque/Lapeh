<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

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
}
