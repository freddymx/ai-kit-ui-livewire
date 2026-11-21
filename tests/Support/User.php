<?php

declare(strict_types=1);

namespace SolutionForest\AiKit\Ui\Tests\Support;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * A minimal participant for the conversation-history tests.
 */
class User extends Authenticatable
{
    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = false;
}
