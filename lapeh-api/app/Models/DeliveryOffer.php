<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class DeliveryOffer extends Model
{
    protected $fillable = ['order_id','driver_id','status','offered_at','responded_at'];
    protected function casts(): array {
        return ['offered_at' => 'datetime', 'responded_at' => 'datetime'];
    }
    public function order() { return $this->belongsTo(Order::class); }
    public function driver() { return $this->belongsTo(Driver::class); }
}
