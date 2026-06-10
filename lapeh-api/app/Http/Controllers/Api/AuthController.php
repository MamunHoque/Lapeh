<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages(['phone' => 'Invalid credentials.']);
        }

        if ($user->status === 'suspended') {
            return response()->json(['message' => 'Account suspended.'], 403);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    public function registerDriver(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:6',
            'vehicle_type' => 'required|in:bike,car',
            'vehicle_plate' => 'nullable|string',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'password' => $data['password'],
            'role' => 'driver',
        ]);

        Driver::create([
            'user_id' => $user->id,
            'vehicle_type' => $data['vehicle_type'],
            'vehicle_plate' => $data['vehicle_plate'] ?? null,
        ]);

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $this->userPayload($user)], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->userPayload($request->user())]);
    }

    public function updateFcmToken(Request $request): JsonResponse
    {
        $request->validate(['fcm_token' => 'required|string']);
        $request->user()->update(['fcm_token' => $request->fcm_token]);
        return response()->json(['message' => 'FCM token updated.']);
    }

    public function updateLocale(Request $request): JsonResponse
    {
        $request->validate(['locale' => 'required|in:en,ar']);
        $request->user()->update(['locale' => $request->locale]);
        return response()->json(['message' => 'Locale updated.']);
    }

    protected function userPayload(User $user): array
    {
        $payload = [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
            'locale' => $user->locale,
            'avatar' => $user->avatar,
        ];

        if ($user->isDriver() && $user->driver) {
            $payload['driver'] = [
                'id' => $user->driver->id,
                'status' => $user->driver->status,
                'vehicle_type' => $user->driver->vehicle_type,
                'vehicle_plate' => $user->driver->vehicle_plate,
                'rating_avg' => $user->driver->rating_avg,
                'is_verified' => $user->driver->is_verified,
            ];
        }

        if ($user->isRestaurant() && $user->restaurant) {
            $payload['restaurant'] = [
                'id' => $user->restaurant->id,
                'name' => $user->restaurant->name,
                'logo' => $user->restaurant->logo,
            ];
        }

        return $payload;
    }
}
