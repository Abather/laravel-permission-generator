<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Permission Naming
    |--------------------------------------------------------------------------
    |
    | Controls how permission names are generated for your models.
    |
    | model_ability_separator: Separator between the ability and model name.
    |     Accepts a literal symbol (".", "|", ":") or a named alias:
    |     space, comma, dot, pipe, colon, semicolon, arrow.
    |
    | model_name_case / ability_name_case: snake, camel, or studly.
    |     Falls back to snake when omitted or unsupported.
    |
    | model_name_position: before or after the ability name. Default: before.
    |
    | use_model_class_base_name: When false, the model's FQCN is used and
    |     model_name_case is ignored.
    |
    | Examples for App\Models\BlogPost with the 'update' ability:
    |
    |     separator: dot,   case: snake,  position: before  =>  blog_post.update
    |     separator: colon, case: camel,  position: after   =>  update:blogPost
    |     separator: arrow, case: studly, position: after   =>  Update → BlogPost
    |     use_model_class_base_name: false, separator: dot  =>  App\Models\BlogPost.update
    |
    */

    'naming' => [
        'model_ability_separator' => '.',
        'model_name_case' => 'snake',
        'ability_name_case' => 'snake',
        'model_name_position' => 'before',
        'use_model_class_base_name' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Guards
    |--------------------------------------------------------------------------
    |
    | Define one entry per guard you want to generate permissions for.
    | Duplicate the "web" example below for additional guards.
    |
    */

    'guards' => [

        'web' => [

            /*
            |------------------------------------------------------------------
            | Models
            |------------------------------------------------------------------
            |
            | The model classes to generate permissions for under this guard.
            |
            | Example:
            |
            |     'models' => [
            |         App\Models\BlogPost::class,
            |         App\Models\Category::class,
            |     ],
            |
            */

            'models' => [],

            /*
            |------------------------------------------------------------------
            | Excluded Global Models
            |------------------------------------------------------------------
            |
            | Model classes listed here are removed from the top-level
            | 'models' list for this guard only. Use this when a global model
            | should not generate permissions under a specific guard.
            |
            | Note: if the same model is also listed in this guard's 'models'
            | key above, the exclusion is ignored and the model is included.
            |
            | Example:
            |
            |     'except' => [
            |         App\Models\AdminLog::class,
            |     ],
            |
            */

            'except' => [],

            /*
            |------------------------------------------------------------------
            | Default Abilities
            |------------------------------------------------------------------
            |
            | Abilities applied to every model listed above. A model may
            | override this list per guard by defining:
            |
            |     public static $abilities = [
            |         'web' => ['view', 'update'],
            |     ];
            |
            */

            'abilities' => [
                'view',
                'view_any',
                'create',
                'update',
                'restore',
                'delete',
                'force_delete',
            ],

            /*
            |------------------------------------------------------------------
            | Custom Abilities
            |------------------------------------------------------------------
            |
            | Extra abilities for specific models, added on top of the
            | default abilities. Keyed by class:
            |
            |     'custom_abilities' => [
            |         App\Models\BlogPost::class => ['publish', 'archive'],
            |     ],
            |
            | These may also be defined on the model itself, keyed by guard:
            |
            |     public static $custom_abilities = [
            |         'web' => ['publish', 'archive'],
            |     ];
            |
            */

            'custom_abilities' => [],

            /*
            |------------------------------------------------------------------
            | Other Permissions
            |------------------------------------------------------------------
            |
            | Permissions not tied to any model. These are created exactly
            | as written, ignoring the naming settings above.
            |
            | Example:
            |
            |     'other_permissions' => [
            |         'viewPulse',
            |         'viewHorizon',
            |         'access_admin_panel',
            |     ],
            |
            */

            'other_permissions' => [],

            /*
            |------------------------------------------------------------------
            | Super Role
            |------------------------------------------------------------------
            |
            | Optional. When set, the role is created if it does not exist,
            | and all permissions for this guard are assigned to it.
            | Set to null to disable.
            |
            |     'super_role' => 'admin',
            |
            */

            'super_role' => 'admin',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Models
    |--------------------------------------------------------------------------
    |
    | Models listed here are included in every guard automatically, in addition
    | to any models defined inside individual guard blocks.
    |
    | Example:
    |
    |     'models' => [
    |         App\Models\BlogPost::class,
    |         App\Models\Category::class,
    |     ],
    |
    */

    'models' => [],

    /*
    |--------------------------------------------------------------------------
    | Guard Config Files Path
    |--------------------------------------------------------------------------
    |
    | The directory, relative to Laravel's config path, where per-guard config
    | files generated by "php artisan permission:guard-config" are stored.
    | Each file is named after its guard (e.g. api.php) and returns the same
    | array structure as an inline guard block in the 'guards' key above.
    |
    */

    'guards_path' => 'laravelPermissionGaurds',
];
