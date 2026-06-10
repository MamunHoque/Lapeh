<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    protected $fillable = ['to','template_key','body','status','provider_ref'];
}
