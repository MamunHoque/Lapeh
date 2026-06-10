<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false;
    protected $fillable = ['user_id','action','subject_type','subject_id','meta'];
    protected function casts(): array { return ['meta' => 'array', 'created_at' => 'datetime']; }
    public function user() { return $this->belongsTo(User::class); }
}
