<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'user_id','fleet_id','vehicle_type','vehicle_plate',
        'status','current_lat','current_lng','last_location_at',
        'rating_avg','rating_count','is_verified',
    ];
    protected function casts(): array {
        return [
            'last_location_at' => 'datetime',
            'is_verified' => 'boolean',
            'current_lat' => 'float',
            'current_lng' => 'float',
            'rating_avg' => 'float',
        ];
    }
    public function user() { return $this->belongsTo(User::class); }
    public function fleet() { return $this->belongsTo(Fleet::class); }
    public function orders() { return $this->hasMany(Order::class); }
    public function offers() { return $this->hasMany(DeliveryOffer::class); }
    public function ratings() { return $this->hasMany(DriverRating::class); }
}
