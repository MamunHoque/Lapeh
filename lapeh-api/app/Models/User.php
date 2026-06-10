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
}
