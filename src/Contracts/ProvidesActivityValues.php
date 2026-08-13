<?php

declare(strict_types=1);

namespace BokshornIt\FilamentActivityTimeline\Contracts;

/**
 * Lets a model render its own logged values.
 *
 * The formatter resolves what the schema tells it - enums, date casts, foreign
 * keys, morph columns - but not what a column *means*: that "total_cents" is
 * money, that a quantity carries three decimals it should not show, that a
 * custom cast holds a calendar day. A model implementing this decides for the
 * columns it knows and returns null for the rest, which fall through to the
 * formatter's own handling.
 *
 * The value arrives as it was logged, which is the raw database value rather
 * than the cast one. Booleans and empty values are settled before this is
 * called, so an implementation never has to answer for them.
 */
interface ProvidesActivityValues
{
    public function formatActivityValue(string $key, mixed $value): ?string;
}
