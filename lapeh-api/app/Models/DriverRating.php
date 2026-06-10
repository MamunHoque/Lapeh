<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class DriverRating extends Model
{
    protected $fillable = ['order_id','sender_id','driver_id','rating','tags','comment'];
    protected function casts(): array { return ['tags' => 'array']; }
    public function order() { return $this->belongsTo(Order::class); }
    public function driver() { return $this->belongsTo(Driver::class); }
    public function sender() { return $this->belongsTo(Sender::class); }
}
