<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'actor_role', 'action', 'subject_type', 'subject_id', 'ip_address', 'meta'];

    protected function casts(): array
    {
        return ['meta' => 'array', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record an audit event. Never throws — logging must not break the request.
     *
     * @param  string       $action   Dotted action key, e.g. "order.created".
     * @param  Model|null   $subject  The affected model (type + id are derived).
     * @param  array        $meta     Extra context to store (amounts, names, etc).
     * @param  User|null    $actor    Defaults to the authenticated user.
     * @param  string|null  $role     Override actor role (e.g. "customer" for guest actions).
     */
    public static function record(string $action, ?Model $subject = null, array $meta = [], ?User $actor = null, ?string $role = null): void
    {
        try {
            $actor ??= Auth::user();

            static::create([
                'user_id' => $actor?->id,
                'actor_role' => $role ?? $actor?->role ?? 'system',
                'action' => $action,
                'subject_type' => $subject ? class_basename($subject) : null,
                'subject_id' => $subject?->getKey(),
                'ip_address' => request()?->ip(),
                'meta' => $meta ?: null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('ActivityLog.record failed', ['action' => $action, 'error' => $e->getMessage()]);
        }
    }
}
