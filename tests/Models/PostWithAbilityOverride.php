<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;

class PostWithAbilityOverride extends Model
{
    public static array $abilities = [
        'web' => ['read'],
    ];
}
