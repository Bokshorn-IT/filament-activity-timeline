<?php

declare(strict_types=1);

namespace BokshornIt\FilamentActivityTimeline\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Spatie\Activitylog\Models\Activity;

/**
 * Optional policy for the activity log, using the permission names
 * filament-shield generates for ActivityResource. Not registered for you:
 *
 *     Gate::policy(Activity::class, ActivityPolicy::class);
 *
 * Create, update and delete return false instead of checking a permission.
 * Restore here means writing an entry's old values back onto its subject, and
 * is limited on top of that to the models listed on ->restorable().
 */
class ActivityPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $user): bool
    {
        return $user->can('view_any_activity');
    }

    public function view(AuthUser $user, Activity $activity): bool
    {
        return $user->can('view_activity');
    }

    public function create(AuthUser $user): bool
    {
        return false;
    }

    public function update(AuthUser $user, Activity $activity): bool
    {
        return false;
    }

    public function delete(AuthUser $user, Activity $activity): bool
    {
        return false;
    }

    public function deleteAny(AuthUser $user): bool
    {
        return false;
    }

    public function forceDelete(AuthUser $user, Activity $activity): bool
    {
        return false;
    }

    public function forceDeleteAny(AuthUser $user): bool
    {
        return false;
    }

    /**
     * Permission to write a logged entry's old values back onto its subject.
     */
    public function restore(AuthUser $user, Activity $activity): bool
    {
        return $user->can('restore_activity');
    }

    public function restoreAny(AuthUser $user): bool
    {
        return false;
    }

    public function replicate(AuthUser $user, Activity $activity): bool
    {
        return false;
    }

    public function reorder(AuthUser $user): bool
    {
        return false;
    }
}
