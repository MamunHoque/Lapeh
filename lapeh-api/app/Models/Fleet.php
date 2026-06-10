<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fleet extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['user_id','company_name','contact_phone','commission_rate','status'];
    public function user() { return $this->belongsTo(User::class); }
    public function drivers() { return $this->hasMany(Driver::class); }
}
