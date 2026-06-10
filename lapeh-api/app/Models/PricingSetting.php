<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PricingSetting extends Model
{
    protected $fillable = ['base_fee','per_km_fee','min_fee','currency','search_radius_km','request_timeout_sec'];
    protected function casts(): array { return ['base_fee' => 'float', 'per_km_fee' => 'float', 'min_fee' => 'float', 'search_radius_km' => 'float']; }

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'base_fee' => 7.00, 'per_km_fee' => 1.50, 'min_fee' => 7.00,
            'currency' => 'AED', 'search_radius_km' => 5.00, 'request_timeout_sec' => 30,
        ]);
    }
}
