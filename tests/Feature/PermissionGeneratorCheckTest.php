<?php

use Abather\LaravelPermissionGenerator\PermissionGenerator;
use Spatie\Permission\Models\Permission;
use Tests\Models\Post;
use Tests\Models\User;

// ── Helpers ───────────────────────────────────────────────────────────────────

function makeUser(): User
{
    return User::create([
        'name'     => 'Test User',
        'email'    => 'user'.uniqid().'@example.com',
        'password' => 'secret',
    ]);
}

function givePermission(User $user, string $permissionName, string $guard = 'web'): void
{
    $permission = Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => $guard]);
    $user->givePermissionTo($permission);
}

// ── check() ───────────────────────────────────────────────────────────────────

it('check() returns true when the user has the permission', function () {
    $user = makeUser();
    givePermission($user, 'post.view');

    expect(PermissionGenerator::check(Post::class, 'view', $user))->toBeTrue();
});

it('check() returns false when the user does not have the permission', function () {
    $user = makeUser();

    expect(PermissionGenerator::check(Post::class, 'view', $user))->toBeFalse();
});

it('check() returns false when the user has a different permission but not this one', function () {
    $user = makeUser();
    givePermission($user, 'post.create');

    expect(PermissionGenerator::check(Post::class, 'view', $user))->toBeFalse();
});

it('check() returns false when the user is null', function () {
    expect(PermissionGenerator::check(Post::class, 'view', null))->toBeFalse();
});

// ── hasPermission() ───────────────────────────────────────────────────────────

it('hasPermission() returns true when the user has the permission', function () {
    $user = makeUser();
    givePermission($user, 'post.view');

    expect(PermissionGenerator::make(Post::class, 'view')->hasPermission($user))->toBeTrue();
});

it('hasPermission() returns false when the user does not have the permission', function () {
    $user = makeUser();

    expect(PermissionGenerator::make(Post::class, 'view')->hasPermission($user))->toBeFalse();
});

it('hasPermission() returns false when the user is null', function () {
    expect(PermissionGenerator::make(Post::class, 'view')->hasPermission(null))->toBeFalse();
});

// ── check() respects permission name resolution ───────────────────────────────

it('check() resolves the permission name using the naming config before checking', function () {
    $prop = new ReflectionProperty(PermissionGenerator::class, 'namingConfig');
    $prop->setValue(null, null);

    config(['laravel-permission-generator.naming.model_ability_separator' => 'colon']);

    $user = makeUser();
    givePermission($user, 'post:view');  // colon separator

    expect(PermissionGenerator::check(Post::class, 'view', $user))->toBeTrue();
    expect(PermissionGenerator::check(Post::class, 'create', $user))->toBeFalse();
});
