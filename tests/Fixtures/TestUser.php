<?php

declare(strict_types=1);

namespace BokshornIt\FilamentActivityTimeline\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Deliberately does not implement ProvidesActivityTitle, so the causer
 * resolver's fallback to the "name" attribute is exercised.
 */
class TestUser extends Authenticatable
{
    protected $table = 'test_users';

    protected $fillable = ['name', 'email'];
}
