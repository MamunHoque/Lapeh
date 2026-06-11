<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LapehNotification extends Model
{
    protected $table = 'lapeh_notifications';

    protected $fillable = ['user_id', 'title', 'body', 'data', 'read_at'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
