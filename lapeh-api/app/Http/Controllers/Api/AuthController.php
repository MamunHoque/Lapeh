<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Driver;
use App\Models\Sender;
use App\Models\User;
use App\Services\OtpService;
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

        ActivityLog::record('auth.login', $user, ['name' => $user->name], $user);

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    /**
     * Register a sender (individual or business). Account starts unverified;
     * the caller must verify the phone OTP before creating delivery requests.
     */
    public function registerSender(Request $request, OtpService $otp): JsonResponse
    {
        abort_unless(settings('registration.sender_enabled', true), 403, 'Sender registration is currently closed.');

        $data = $request->validate([
            'type' => 'required|in:individual,business',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:6',
            'default_pickup_address' => 'nullable|string|max:500',
            'default_pickup_lat' => 'nullable|numeric|between:-90,90',
            'default_pickup_lng' => 'nullable|numeric|between:-180,180',
            // Business-only
            'business_name' => 'required_if:type,business|nullable|string|max:255',
            'business_category' => 'nullable|string|max:255',
            'contact_person_name' => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'password' => $data['password'],
            'role' => 'sender',
        ]);

        Sender::create([
            'user_id' => $user->id,
            'type' => $data['type'],
            'business_name' => $data['type'] === 'business' ? ($data['business_name'] ?? null) : null,
            'business_category' => $data['type'] === 'business' ? ($data['business_category'] ?? null) : null,
            'contact_person_name' => $data['type'] === 'business' ? ($data['contact_person_name'] ?? null) : null,
            'default_pickup_address' => $data['default_pickup_address'] ?? null,
            'default_pickup_lat' => $data['default_pickup_lat'] ?? null,
            'default_pickup_lng' => $data['default_pickup_lng'] ?? null,
            'status' => 'pending',
        ]);

        $code = $otp->generate($user);
        $token = $user->createToken('mobile')->plainTextToken;

        ActivityLog::record('auth.register', $user, ['name' => $user->name, 'type' => $data['type']], $user);

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user->fresh('sender')),
            // Exposed only in non-production so testing needs no SMS provider.
            'dev_otp' => $otp->isDevEnv() ? $code : null,
        ], 201);
    }

    public function verifyOtp(Request $request, OtpService $otp): JsonResponse
    {
        $request->validate(['code' => 'required|string']);
        $user = $request->user();

        if ($user->isPhoneVerified()) {
            return response()->json(['user' => $this->userPayload($user), 'message' => 'Already verified.']);
        }

        if (! $otp->verify($user, $request->code)) {
            throw ValidationException::withMessages(['code' => 'Invalid or expired code.']);
        }

        ActivityLog::record('auth.phone_verified', $user, [], $user);

        return response()->json(['user' => $this->userPayload($user->fresh('sender'))]);
    }

    public function resendOtp(Request $request, OtpService $otp): JsonResponse
    {
        $user = $request->user();
        if ($user->isPhoneVerified()) {
            return response()->json(['message' => 'Already verified.']);
        }
        $code = $otp->generate($user);
        return response()->json([
            'message' => 'OTP sent.',
            'dev_otp' => $otp->isDevEnv() ? $code : null,
        ]);
    }

    public function registerDriver(Request $request): JsonResponse
    {
        abort_unless(settings('registration.driver_enabled', true), 403, 'Driver registration is currently closed.');

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

        ActivityLog::record('auth.register', $user, ['name' => $user->name, 'vehicle_type' => $data['vehicle_type']], $user);

        return response()->json(['token' => $token, 'user' => $this->userPayload($user)], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        ActivityLog::record('auth.logout', $request->user(), ['name' => $request->user()->name]);
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

    /**
     * Update the authenticated user's own account fields (any role).
     * Phone is intentionally read-only — changing it requires a fresh OTP flow.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'nullable|email|max:255',
            'avatar' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
        ]);

        $update = [];
        if ($request->has('name')) $update['name'] = $data['name'];
        if ($request->has('email')) $update['email'] = $data['email'];

        if ($request->hasFile('avatar')) {
            // Drop the previous file so avatars don't accumulate on disk.
            if ($user->avatar) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar);
            }
            $update['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($update);

        ActivityLog::record('auth.profile_updated', $user, ['name' => $user->name], $user);

        return response()->json(['user' => $this->userPayload($user->fresh(['sender', 'driver']))]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'Current password is incorrect.']);
        }

        $user->update(['password' => $request->password]);

        ActivityLog::record('auth.password_changed', $user, [], $user);

        return response()->json(['message' => 'Password updated.']);
    }

    protected function userPayload(User $user): array
    {
        return $user->apiPayload();
    }
}
