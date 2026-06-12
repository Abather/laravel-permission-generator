<?php

use Abather\LaravelPermissionGenerator\PermissionGenerator;

// Reset the static naming config cache between every test so that
// config overrides within a test are not poisoned by a previous run.
beforeEach(function () {
    $prop = new ReflectionProperty(PermissionGenerator::class, 'namingConfig');
    $prop->setValue(null, null);
});

// ── Helpers ─────────────────────────────────────────────────────────────────

function naming(array $overrides): void
{
    config(['laravel-permission-generator.naming' => array_merge(
        config('laravel-permission-generator.naming'),
        $overrides,
    )]);
}

// ── Default config ───────────────────────────────────────────────────────────

it('builds a permission name with the default config', function () {
    expect(PermissionGenerator::make('App\\Models\\BlogPost', 'update')->permission())
        ->toBe('blog_post.update');
});

// ── Separator aliases ────────────────────────────────────────────────────────

it('resolves the dot alias to a period', function () {
    naming(['model_ability_separator' => 'dot']);

    expect(PermissionGenerator::make('App\\Models\\Post', 'view')->permission())
        ->toBe('post.view');
});

it('resolves the colon alias', function () {
    naming(['model_ability_separator' => 'colon']);

    expect(PermissionGenerator::make('App\\Models\\Post', 'view')->permission())
        ->toBe('post:view');
});

it('resolves the pipe alias', function () {
    naming(['model_ability_separator' => 'pipe']);

    expect(PermissionGenerator::make('App\\Models\\Post', 'view')->permission())
        ->toBe('post|view');
});

it('resolves the space alias', function () {
    naming(['model_ability_separator' => 'space']);

    expect(PermissionGenerator::make('App\\Models\\Post', 'view')->permission())
        ->toBe('post view');
});

it('resolves the comma alias', function () {
    naming(['model_ability_separator' => 'comma']);

    expect(PermissionGenerator::make('App\\Models\\Post', 'view')->permission())
        ->toBe('post,view');
});

it('resolves the semicolon alias', function () {
    naming(['model_ability_separator' => 'semicolon']);

    expect(PermissionGenerator::make('App\\Models\\Post', 'view')->permission())
        ->toBe('post;view');
});

it('resolves the arrow alias to the → character', function () {
    naming(['model_ability_separator' => 'arrow']);

    expect(PermissionGenerator::make('App\\Models\\Post', 'view')->permission())
        ->toBe('post→view');
});

it('passes through a literal separator character unchanged', function () {
    naming(['model_ability_separator' => '-']);

    expect(PermissionGenerator::make('App\\Models\\Post', 'view')->permission())
        ->toBe('post-view');
});

// ── Model name case ──────────────────────────────────────────────────────────

it('converts the model name to snake_case', function () {
    naming(['model_name_case' => 'snake']);

    expect(PermissionGenerator::make('App\\Models\\BlogPost', 'view')->permission())
        ->toBe('blog_post.view');
});

it('converts the model name to camelCase', function () {
    naming(['model_name_case' => 'camel']);

    expect(PermissionGenerator::make('App\\Models\\BlogPost', 'view')->permission())
        ->toBe('blogPost.view');
});

it('converts the model name to StudlyCase', function () {
    naming(['model_name_case' => 'studly']);

    expect(PermissionGenerator::make('App\\Models\\BlogPost', 'view')->permission())
        ->toBe('BlogPost.view');
});

it('falls back to snake_case for an unknown model_name_case value', function () {
    naming(['model_name_case' => 'unknown']);

    expect(PermissionGenerator::make('App\\Models\\BlogPost', 'view')->permission())
        ->toBe('blog_post.view');
});

// ── Ability name case ────────────────────────────────────────────────────────

it('converts the ability to snake_case', function () {
    naming(['ability_name_case' => 'snake']);

    expect(PermissionGenerator::make('App\\Models\\Post', 'viewAny')->permission())
        ->toBe('post.view_any');
});

it('converts the ability to camelCase', function () {
    naming(['ability_name_case' => 'camel']);

    expect(PermissionGenerator::make('App\\Models\\Post', 'view_any')->permission())
        ->toBe('post.viewAny');
});

it('converts the ability to StudlyCase', function () {
    naming(['ability_name_case' => 'studly']);

    expect(PermissionGenerator::make('App\\Models\\Post', 'view_any')->permission())
        ->toBe('post.ViewAny');
});

it('falls back to snake_case for an unknown ability_name_case value', function () {
    naming(['ability_name_case' => 'unknown']);

    expect(PermissionGenerator::make('App\\Models\\Post', 'viewAny')->permission())
        ->toBe('post.view_any');
});

// ── Position ─────────────────────────────────────────────────────────────────

it('places the model before the ability by default', function () {
    naming(['model_name_position' => 'before']);

    expect(PermissionGenerator::make('App\\Models\\Post', 'update')->permission())
        ->toBe('post.update');
});

it('places the model after the ability when position is "after"', function () {
    naming(['model_name_position' => 'after']);

    expect(PermissionGenerator::make('App\\Models\\Post', 'update')->permission())
        ->toBe('update.post');
});

// ── FQCN ─────────────────────────────────────────────────────────────────────

it('uses the fully-qualified class name when use_model_class_base_name is false', function () {
    naming(['use_model_class_base_name' => false]);

    expect(PermissionGenerator::make('App\\Models\\BlogPost', 'update')->permission())
        ->toBe('App\\Models\\BlogPost.update');
});

it('ignores model_name_case when using the FQCN', function () {
    naming(['use_model_class_base_name' => false, 'model_name_case' => 'camel']);

    expect(PermissionGenerator::make('App\\Models\\BlogPost', 'update')->permission())
        ->toBe('App\\Models\\BlogPost.update');
});

// ── Memoization ──────────────────────────────────────────────────────────────

it('returns the same string on repeated calls without recomputing', function () {
    $generator = PermissionGenerator::make('App\\Models\\Post', 'view');

    $first  = $generator->permission();
    $second = $generator->permission();

    expect($first)->toBe($second)->toBe('post.view');
});

// ── ability() fluent method ───────────────────────────────────────────────────

it('recalculates the permission when ability() is called on an existing instance', function () {
    $generator = PermissionGenerator::make('App\\Models\\Post', 'view');

    expect($generator->permission())->toBe('post.view');
    expect($generator->ability('update')->permission())->toBe('post.update');
});

// ── make() factory ───────────────────────────────────────────────────────────

it('make() returns a PermissionGenerator instance', function () {
    expect(PermissionGenerator::make('App\\Models\\Post', 'view'))
        ->toBeInstanceOf(PermissionGenerator::class);
});

// ── hasPermission() ──────────────────────────────────────────────────────────

it('returns false when the user is null', function () {
    expect(PermissionGenerator::make('App\\Models\\Post', 'view')->hasPermission(null))
        ->toBeFalse();
});

// ── check() shorthand ────────────────────────────────────────────────────────

it('check() returns false when the user is null', function () {
    expect(PermissionGenerator::check('App\\Models\\Post', 'view', null))
        ->toBeFalse();
});
