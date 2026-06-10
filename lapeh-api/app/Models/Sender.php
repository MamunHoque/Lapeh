<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sender extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'type', 'business_name', 'business_category', 'contact_person_name',
        'default_pickup_address', 'default_pickup_lat', 'default_pickup_lng', 'status',
    ];

    protected function casts(): array
    {
        return [
            'default_pickup_lat' => 'float',
            'default_pickup_lng' => 'float',
        ];
    }

    public function user() { return $this->belongsTo(User::class); }
    public function orders() { return $this->hasMany(Order::class); }
    public function complaints() { return $this->hasMany(Complaint::class); }
    public function ratings() { return $this->hasMany(DriverRating::class); }

    public function isBusiness(): bool { return $this->type === 'business'; }

    /** Display name: business name for businesses, the user's name otherwise. */
    public function displayName(): string
    {
        return $this->isBusiness() && $this->business_name
            ? $this->business_name
            : ($this->user->name ?? 'Sender');
    }
}
