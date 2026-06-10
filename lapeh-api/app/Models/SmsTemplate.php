<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SmsTemplate extends Model
{
    protected $fillable = ['key','content_en','content_ar','variables'];
    protected function casts(): array { return ['variables' => 'array']; }
}
