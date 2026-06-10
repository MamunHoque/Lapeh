<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['order_id','amount','currency','gateway','gateway_reference','status','paid_at','raw_payload'];
    protected function casts(): array { return ['paid_at' => 'datetime', 'raw_payload' => 'array', 'amount' => 'float']; }
    public function order() { return $this->belongsTo(Order::class); }
}
