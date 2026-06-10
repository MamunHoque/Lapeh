<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Zone extends Model
{
    use HasFactory;
    protected $fillable = ['name','polygon','base_fee','per_km_fee','status'];
    protected function casts(): array { return ['polygon' => 'array', 'base_fee' => 'float', 'per_km_fee' => 'float']; }
}
