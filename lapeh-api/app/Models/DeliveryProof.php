<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class DeliveryProof extends Model
{
    protected $fillable = ['order_id','photo_path','signature_path','otp_verified','captured_at'];
    protected function casts(): array { return ['otp_verified' => 'boolean', 'captured_at' => 'datetime']; }
    public function order() { return $this->belongsTo(Order::class); }
}
