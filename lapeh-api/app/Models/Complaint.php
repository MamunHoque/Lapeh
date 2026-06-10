<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    protected $fillable = ['order_id','sender_id','type','description','status','resolution_note','resolved_by'];
    public function order() { return $this->belongsTo(Order::class); }
    public function sender() { return $this->belongsTo(Sender::class); }
    public function resolver() { return $this->belongsTo(User::class, 'resolved_by'); }
    public function attachments() { return $this->hasMany(ComplaintAttachment::class); }
}
