<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Shared date-range filtering for admin list pages.
 *
 * Reads `range` (today|yesterday|7d|month|year|custom) plus `from`/`to` for the
 * custom case from the request, and constrains a query on the given column.
 */
class DateRange
{
    /** Preset keys exposed in the UI dropdown. */
    public const PRESETS = ['today', 'yesterday', '7d', 'month', 'year'];

    /**
     * Resolve [from, to] Carbon bounds for the request (nulls = unbounded).
     *
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    public static function resolve(Request $request): array
    {
        $now = Carbon::now();
        $range = $request->query('range');

        return match ($range) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            '7d' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            'month' => [$now->copy()->subMonth()->startOfDay(), $now->copy()->endOfDay()],
            'year' => [$now->copy()->subYear()->startOfDay(), $now->copy()->endOfDay()],
            'custom' => [
                self::parse($request->query('from'))?->startOfDay(),
                self::parse($request->query('to'))?->endOfDay(),
            ],
            default => [null, null],
        };
    }

    /**
     * Apply the request's date range to a query on $column. No-op when no
     * range/dates are provided.
     */
    public static function apply(Builder $query, Request $request, string $column = 'created_at'): Builder
    {
        [$from, $to] = self::resolve($request);

        if ($from) {
            $query->where($column, '>=', $from);
        }
        if ($to) {
            $query->where($column, '<=', $to);
        }

        return $query;
    }

    private static function parse(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }
        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
