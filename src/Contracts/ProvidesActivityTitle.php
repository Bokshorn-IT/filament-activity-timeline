<?php

declare(strict_types=1);

namespace BokshornIt\FilamentActivityTimeline\Contracts;

/**
 * Lets a model name itself in the activity log.
 *
 * Used for the subject of an entry, the causer of one, and for foreign keys in
 * a diff, so "customer_id: 12" reads as "Muster GmbH".
 */
interface ProvidesActivityTitle
{
    public function activityTitle(): ?string;
}
