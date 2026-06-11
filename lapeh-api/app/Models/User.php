<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name', 'email', 'phone', 'password',
        'role', 'status', 'locale', 'fcm_token', 'avatar',
        'phone_verified_at', 'phone_otp_hash', 'phone_otp_expires_at',
    ];

    protected $hidden = ['password', 'remember_token', 'phone_otp_hash'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'phone_otp_expires_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function sender(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Sender::class);
    }

    public function driver(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Driver::class);
    }

    public function fleet(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Fleet::class);
    }

    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function isSender(): bool { return $this->role === 'sender'; }
    public function isDriver(): bool { return $this->role === 'driver'; }

    public function isPhoneVerified(): bool { return $this->phone_verified_at !== null; }

    public function lapehNotifications(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LapehNotification::class);
    }

    /** Public-facing user payload shared by auth & sender profile endpoints. */
    public function apiPayload(): array
    {
        $payload = [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'role' => $this->role,
            'status' => $this->status,
            'locale' => $this->locale,
            // Return a fully-qualified URL so the apps can load it directly.
            'avatar' => $this->avatar ? asset("storage/{$this->avatar}") : null,
            'phone_verified' => $this->isPhoneVerified(),
        ];

        if ($this->isDriver() && $this->driver) {
            $payload['driver'] = [
                'id' => $this->driver->id,
                'status' => $this->driver->status,
                'vehicle_type' => $this->driver->vehicle_type,
                'vehicle_plate' => $this->driver->vehicle_plate,
                'rating_avg' => $this->driver->rating_avg,
                'is_verified' => $this->driver->is_verified,
            ];
        }

        if ($this->isSender() && $this->sender) {
            $s = $this->sender;
            $payload['sender'] = [
                'id' => $s->id,
                'type' => $s->type,
                'business_name' => $s->business_name,
                'business_category' => $s->business_category,
                'contact_person_name' => $s->contact_person_name,
                'default_pickup_address' => $s->default_pickup_address,
                'default_pickup_lat' => $s->default_pickup_lat,
                'default_pickup_lng' => $s->default_pickup_lng,
                'status' => $s->status,
            ];
        }

        return $payload;
    }
}
