<?php

namespace Tests\Models;

use Illuminate\Database\Eloquent\Model;

class PostWithCustomAbility extends Model
{
    public static array $custom_abilities = [
        'web' => ['publish'],
    ];
}
