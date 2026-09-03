<?php

namespace Harvirsidhu\FilamentCards\Support;

/**
 * Normalises a widget-style columns config into the breakpoint array that
 * Filament's `$attributes->grid()` macro expects.
 *
 * The view used to build Tailwind classes by string interpolation
 * ("{$prefix}grid-cols-{$count}"). Tailwind only ever sees literal strings
 * when it scans a Blade file, so none of those utilities were generated and
 * every responsive columns config silently rendered as a single column.
 * Handing the numbers to Filament's macro instead emits `--cols-md` custom
 * properties against classes that already ship compiled in Filament's CSS,
 * so there is nothing left for a user's Tailwind build to miss.
 */
class GridColumns
{
    protected const MAX_COLUMNS = 12;

    /**
     * The progressive default applied to a scalar `columns(4)`, preserved
     * from the previous hand-rolled implementation so existing pages keep
     * the layout they already have — except that counts above four now
     * actually reach their 2xl step, which the old code could not generate.
     *
     * @var array<int, string>
     */
    protected const SCALAR_BREAKPOINTS = [2 => 'md', 3 => 'lg', 4 => 'xl'];

    /**
     * @param  int|string|array<string, int|string|null>|null  $columns
     * @return array<string, int>
     */
    public static function normalize(int | string | array | null $columns): array
    {
        if (is_array($columns)) {
            return static::normalizeArray($columns);
        }

        return static::normalizeScalar($columns);
    }

    /**
     * @return array<string, int>
     */
    protected static function normalizeScalar(int | string | null $columns): array
    {
        $count = static::parse($columns) ?? 3;

        $normalized = ['default' => 1];

        foreach (static::SCALAR_BREAKPOINTS as $step => $breakpoint) {
            if ($count >= $step) {
                $normalized[$breakpoint] = min($step, $count);
            }
        }

        if ($count >= 5) {
            $normalized['2xl'] = $count;
        }

        return $normalized;
    }

    /**
     * @param  array<string, int|string|null>  $columns
     * @return array<string, int>
     */
    protected static function normalizeArray(array $columns): array
    {
        $normalized = [];

        foreach ($columns as $breakpoint => $value) {
            $count = static::parse($value);

            if ($count === null) {
                continue;
            }

            $normalized[$breakpoint] = $count;
        }

        // A responsive config that starts at `md` still needs a base value,
        // otherwise the grid has no column count below that breakpoint.
        $normalized['default'] ??= 1;

        return $normalized;
    }

    protected static function parse(int | string | null $value): ?int
    {
        if (is_int($value)) {
            return max(1, min(static::MAX_COLUMNS, $value));
        }

        if (is_string($value) && is_numeric($value)) {
            return max(1, min(static::MAX_COLUMNS, (int) $value));
        }

        return null;
    }
}
