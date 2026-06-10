<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'order_no','restaurant_id','driver_id','customer_name','customer_phone',
        'order_value','prep_time_min','notes','customer_lat','customer_lng',
        'customer_address','distance_km','delivery_fee','total_amount','status',
        'location_token','payment_status','otp_code',
        'assigned_at','picked_up_at','delivered_at','cancelled_reason',
    ];
    protected function casts(): array {
        return [
            'assigned_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'delivered_at' => 'datetime',
            'order_value' => 'float',
            'delivery_fee' => 'float',
            'total_amount' => 'float',
            'distance_km' => 'float',
            'customer_lat' => 'float',
            'customer_lng' => 'float',
        ];
    }
    public function restaurant() { return $this->belongsTo(Restaurant::class); }
    public function driver() { return $this->belongsTo(Driver::class); }
    public function statusLogs() { return $this->hasMany(OrderStatusLog::class)->orderBy('created_at'); }
    public function offers() { return $this->hasMany(DeliveryOffer::class); }
    public function payment() { return $this->hasOne(Payment::class); }
    public function proof() { return $this->hasOne(DeliveryProof::class); }
    public function rating() { return $this->hasOne(DriverRating::class); }
    public function complaint() { return $this->hasOne(Complaint::class); }

    public function isTerminal(): bool {
        return in_array($this->status, ['delivered', 'cancelled']);
    }
}
