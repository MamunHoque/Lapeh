<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Restaurant extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['user_id','zone_id','name','name_ar','phone','area','address','lat','lng','status','logo'];
    protected function casts(): array { return ['lat' => 'float', 'lng' => 'float']; }
    public function user() { return $this->belongsTo(User::class); }
    public function zone() { return $this->belongsTo(Zone::class); }
    public function orders() { return $this->hasMany(Order::class); }
    public function complaints() { return $this->hasMany(Complaint::class); }
    public function ratings() { return $this->hasMany(DriverRating::class); }
}
