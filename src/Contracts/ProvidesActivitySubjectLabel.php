<?php

declare(strict_types=1);

namespace BokshornIt\FilamentActivityTimeline\Contracts;

/**
 * Lets a model provide its own record type label in the activity log.
 *
 * Null keeps the label of the resource that manages the model.
 */
interface ProvidesActivitySubjectLabel
{
    public function activitySubjectLabel(): ?string;
}
