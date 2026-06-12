<?php

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Models\Article;
use Tests\Models\Post;
use Tests\Models\PostWithAbilityOverride;
use Tests\Models\PostWithCustomAbility;

// ── Helpers ──────────────────────────────────────────────────────────────────

function guardConfig(array $overrides = [], string $guard = 'web'): void
{
    config(['laravel-permission-generator.guards' => [
        $guard => array_merge([
            'models'            => [],
            'except'            => [],
            'abilities'         => ['view', 'create', 'update', 'delete'],
            'custom_abilities'  => [],
            'other_permissions' => [],
            'super_role'        => 'admin',
        ], $overrides),
    ]]);
    config(['laravel-permission-generator.models' => []]);
}

// ── Permission creation ───────────────────────────────────────────────────────

it('creates a permission for every model × ability combination', function () {
    guardConfig(['models' => [Post::class], 'abilities' => ['view', 'create']]);

    $this->artisan('permission:generate-permission')->assertExitCode(0);

    expect(Permission::where('name', 'post.view')->where('guard_name', 'web')->exists())->toBeTrue();
    expect(Permission::where('name', 'post.create')->where('guard_name', 'web')->exists())->toBeTrue();
});

it('does not duplicate permissions that already exist', function () {
    guardConfig(['models' => [Post::class], 'abilities' => ['view']]);

    Permission::create(['name' => 'post.view', 'guard_name' => 'web']);

    $this->artisan('permission:generate-permission');

    expect(Permission::where('name', 'post.view')->where('guard_name', 'web')->count())->toBe(1);
});

it('creates other_permissions exactly as written', function () {
    guardConfig(['other_permissions' => ['viewPulse', 'access_admin_panel']]);

    $this->artisan('permission:generate-permission');

    expect(Permission::where('name', 'viewPulse')->exists())->toBeTrue();
    expect(Permission::where('name', 'access_admin_panel')->exists())->toBeTrue();
});

it('returns a successful exit code', function () {
    guardConfig();

    $this->artisan('permission:generate-permission')->assertExitCode(0);
});

it('calls permission:cache-reset without error', function () {
    guardConfig();

    // permission:cache-reset is always called; verify the command still exits cleanly.
    $this->artisan('permission:generate-permission')->assertExitCode(0);
});

// ── Guard model resolution ────────────────────────────────────────────────────

it('includes global models in every guard', function () {
    config([
        'laravel-permission-generator.models'  => [Post::class],
        'laravel-permission-generator.guards'  => [
            'web' => [
                'models'            => [],
                'except'            => [],
                'abilities'         => ['view'],
                'custom_abilities'  => [],
                'other_permissions' => [],
                'super_role'        => null,
            ],
            'api' => [
                'models'            => [],
                'except'            => [],
                'abilities'         => ['view'],
                'custom_abilities'  => [],
                'other_permissions' => [],
                'super_role'        => null,
            ],
        ],
    ]);

    $this->artisan('permission:generate-permission');

    expect(Permission::where('name', 'post.view')->where('guard_name', 'web')->exists())->toBeTrue();
    expect(Permission::where('name', 'post.view')->where('guard_name', 'api')->exists())->toBeTrue();
});

it('excludes a global model listed in except', function () {
    config([
        'laravel-permission-generator.models' => [Post::class, Article::class],
        'laravel-permission-generator.guards' => [
            'web' => [
                'models'            => [],
                'except'            => [Article::class],
                'abilities'         => ['view'],
                'custom_abilities'  => [],
                'other_permissions' => [],
                'super_role'        => null,
            ],
        ],
    ]);

    $this->artisan('permission:generate-permission');

    expect(Permission::where('name', 'post.view')->exists())->toBeTrue();
    expect(Permission::where('name', 'article.view')->exists())->toBeFalse();
});

it('includes an excepted model when it is also in the guard models list', function () {
    config([
        'laravel-permission-generator.models' => [Post::class],
        'laravel-permission-generator.guards' => [
            'web' => [
                'models'            => [Post::class],
                'except'            => [Post::class],
                'abilities'         => ['view'],
                'custom_abilities'  => [],
                'other_permissions' => [],
                'super_role'        => null,
            ],
        ],
    ]);

    $this->artisan('permission:generate-permission');

    expect(Permission::where('name', 'post.view')->exists())->toBeTrue();
});

// ── Ability resolution ────────────────────────────────────────────────────────

it('uses the model static $abilities for the guard when defined', function () {
    guardConfig(['models' => [PostWithAbilityOverride::class]]);

    $this->artisan('permission:generate-permission');

    // $abilities = ['web' => ['read']] — only 'read' should be created
    expect(Permission::where('name', 'post_with_ability_override.read')->exists())->toBeTrue();
    expect(Permission::where('name', 'post_with_ability_override.view')->exists())->toBeFalse();
    expect(Permission::where('name', 'post_with_ability_override.create')->exists())->toBeFalse();
});

it('appends custom_abilities from the guard config', function () {
    guardConfig([
        'models'           => [Post::class],
        'abilities'        => ['view'],
        'custom_abilities' => [Post::class => ['export']],
    ]);

    $this->artisan('permission:generate-permission');

    expect(Permission::where('name', 'post.view')->exists())->toBeTrue();
    expect(Permission::where('name', 'post.export')->exists())->toBeTrue();
});

it('appends the model static $custom_abilities for the guard', function () {
    guardConfig(['models' => [PostWithCustomAbility::class], 'abilities' => ['view']]);

    $this->artisan('permission:generate-permission');

    // $custom_abilities = ['web' => ['publish']] — added on top of guard abilities
    expect(Permission::where('name', 'post_with_custom_ability.view')->exists())->toBeTrue();
    expect(Permission::where('name', 'post_with_custom_ability.publish')->exists())->toBeTrue();
});

// ── Super role ────────────────────────────────────────────────────────────────

it('creates the super role when it does not exist', function () {
    guardConfig(['models' => [Post::class], 'super_role' => 'admin']);

    $this->artisan('permission:generate-permission');

    expect(Role::where('name', 'admin')->where('guard_name', 'web')->exists())->toBeTrue();
});

it('assigns all guard permissions to the super role', function () {
    guardConfig(['models' => [Post::class], 'abilities' => ['view', 'create'], 'super_role' => 'admin']);

    $this->artisan('permission:generate-permission');

    $role        = Role::where('name', 'admin')->where('guard_name', 'web')->first();
    $permissions = $role->permissions->pluck('name');

    expect($permissions)->toContain('post.view')
                        ->toContain('post.create');
});

it('outputs an error when super_role is null', function () {
    guardConfig(['super_role' => null]);

    $this->artisan('permission:generate-permission')
        ->expectsOutputToContain("web doesn't have super role");
});

// ── Multiple guards ───────────────────────────────────────────────────────────

it('generates permissions separately for each guard', function () {
    config([
        'laravel-permission-generator.models' => [],
        'laravel-permission-generator.guards' => [
            'web' => [
                'models'            => [Post::class],
                'except'            => [],
                'abilities'         => ['view'],
                'custom_abilities'  => [],
                'other_permissions' => [],
                'super_role'        => null,
            ],
            'api' => [
                'models'            => [Article::class],
                'except'            => [],
                'abilities'         => ['view'],
                'custom_abilities'  => [],
                'other_permissions' => [],
                'super_role'        => null,
            ],
        ],
    ]);

    $this->artisan('permission:generate-permission');

    expect(Permission::where('name', 'post.view')->where('guard_name', 'web')->exists())->toBeTrue();
    expect(Permission::where('name', 'article.view')->where('guard_name', 'api')->exists())->toBeTrue();
    // No cross-contamination
    expect(Permission::where('name', 'post.view')->where('guard_name', 'api')->exists())->toBeFalse();
});

it('creates a separate super role per guard', function () {
    config([
        'laravel-permission-generator.models' => [],
        'laravel-permission-generator.guards' => [
            'web' => ['models' => [], 'except' => [], 'abilities' => [], 'custom_abilities' => [], 'other_permissions' => [], 'super_role' => 'admin'],
            'api' => ['models' => [], 'except' => [], 'abilities' => [], 'custom_abilities' => [], 'other_permissions' => [], 'super_role' => 'api-admin'],
        ],
    ]);

    $this->artisan('permission:generate-permission');

    expect(Role::where('name', 'admin')->where('guard_name', 'web')->exists())->toBeTrue();
    expect(Role::where('name', 'api-admin')->where('guard_name', 'api')->exists())->toBeTrue();
});

// ── File-based guard configs ──────────────────────────────────────────────────

describe('file-based guard configs', function () {

    $tmpDir = null;

    afterEach(function () use (&$tmpDir) {
        if ($tmpDir && is_dir($tmpDir)) {
            array_map('unlink', glob($tmpDir.'/*.php'));
            rmdir($tmpDir);
            $tmpDir = null;
        }
    });

    it('loads guard configurations from the configured directory', function () use (&$tmpDir) {
        $subdir = 'test-guards-'.uniqid();
        $tmpDir = app()->configPath($subdir);
        mkdir($tmpDir, 0777, true);

        file_put_contents($tmpDir.'/api.php', sprintf(
            "<?php return ['models' => [%s::class], 'except' => [], 'abilities' => ['view'], 'custom_abilities' => [], 'other_permissions' => [], 'super_role' => null];",
            Post::class
        ));

        config([
            'laravel-permission-generator.models'      => [],
            'laravel-permission-generator.guards'      => [],
            'laravel-permission-generator.guards_path' => $subdir,
        ]);

        $this->artisan('permission:generate-permission');

        expect(Permission::where('name', 'post.view')->where('guard_name', 'api')->exists())->toBeTrue();
    });

    it('does not override an inline guard with a file-based one', function () use (&$tmpDir) {
        $subdir = 'test-guards-'.uniqid();
        $tmpDir = app()->configPath($subdir);
        mkdir($tmpDir, 0777, true);

        // File says 'view' only; inline says 'create' only — inline should win
        file_put_contents($tmpDir.'/web.php', sprintf(
            "<?php return ['models' => [%s::class], 'except' => [], 'abilities' => ['view'], 'custom_abilities' => [], 'other_permissions' => [], 'super_role' => null];",
            Post::class
        ));

        config([
            'laravel-permission-generator.models'      => [],
            'laravel-permission-generator.guards_path' => $subdir,
            'laravel-permission-generator.guards'      => [
                'web' => [
                    'models'            => [Post::class],
                    'except'            => [],
                    'abilities'         => ['create'],
                    'custom_abilities'  => [],
                    'other_permissions' => [],
                    'super_role'        => null,
                ],
            ],
        ]);

        $this->artisan('permission:generate-permission');

        expect(Permission::where('name', 'post.create')->exists())->toBeTrue();
        expect(Permission::where('name', 'post.view')->exists())->toBeFalse();
    });

});

// ── Command output ────────────────────────────────────────────────────────────

it('reports each newly created permission', function () {
    guardConfig(['models' => [Post::class], 'abilities' => ['view'], 'super_role' => null]);

    $this->artisan('permission:generate-permission')
        ->expectsOutputToContain('post.view');
});

it('reports permissions that already exist', function () {
    guardConfig(['models' => [Post::class], 'abilities' => ['view'], 'super_role' => null]);

    Permission::create(['name' => 'post.view', 'guard_name' => 'web']);

    $this->artisan('permission:generate-permission')
        ->expectsOutputToContain('post.view already exists');
});

it('prints a summary of models and guards', function () {
    guardConfig(['models' => [Post::class], 'abilities' => ['view'], 'super_role' => null]);

    // Both "Models" and "guards" are on the same output line — one assertion covers both.
    $this->artisan('permission:generate-permission')
        ->expectsOutputToContain('Models, for')
        ->expectsOutputToContain('Permissions Created');
});
