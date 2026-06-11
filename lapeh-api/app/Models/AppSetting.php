<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A single key/value configuration row in the persistent settings store.
 * Reads/writes go through {@see \App\Services\SettingsService}, which adds
 * caching, type casting and transparent encryption — prefer that over the
 * model directly.
 */
class AppSetting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'type', 'is_encrypted'];

    protected function casts(): array
    {
        return ['is_encrypted' => 'boolean'];
    }
}
