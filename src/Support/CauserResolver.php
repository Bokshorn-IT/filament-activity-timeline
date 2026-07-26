<?php

declare(strict_types=1);

namespace BokshornIt\FilamentActivityTimeline\Support;

use BokshornIt\FilamentActivityTimeline\ActivityTimelinePlugin;
use BokshornIt\FilamentActivityTimeline\Contracts\ProvidesActivityTitle;
use Illuminate\Database\Eloquent\Model;

/**
 * Who performed an activity.
 *
 * Not every actor is a user. A customer in a self-service portal is a
 * different model, and queue jobs or scheduled commands have no causer at all.
 * Those show as "System" instead of an empty cell.
 */
class CauserResolver
{
    public function __construct(
        protected readonly ActivityTimelinePlugin $plugin,
    ) {}

    public static function make(): static
    {
        return new static(ActivityTimelinePlugin::resolve());
    }

    public function label(?Model $causer): string
    {
        if ($causer === null) {
            return __('filament-activity-timeline::activity.causer.system');
        }

        if ($causer instanceof ProvidesActivityTitle) {
            $title = $causer->activityTitle();

            if (filled($title)) {
                return (string) $title;
            }
        }

        $name = $causer->getAttribute('name');

        if (filled($name)) {
            return (string) $name;
        }

        return class_basename($causer).' #'.$causer->getKey();
    }

    public function icon(?Model $causer): string
    {
        if ($causer === null) {
            return $this->plugin->getSystemCauserIcon();
        }

        $icons = $this->plugin->getCauserIcons();

        foreach ($icons as $class => $icon) {
            if ($causer instanceof $class) {
                return $icon;
            }
        }

        return $this->plugin->getDefaultCauserIcon();
    }
}
