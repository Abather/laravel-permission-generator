<?php

use Illuminate\Support\Facades\File;

// Each test writes real files — use a unique subdirectory per test so they
// are fully isolated, and clean it up in afterEach.
$guardsDir = null;

beforeEach(function () use (&$guardsDir) {
    $subdir = 'test-guard-cfg-'.uniqid();
    $guardsDir = app()->configPath($subdir);

    config(['laravel-permission-generator.guards_path' => $subdir]);
});

afterEach(function () use (&$guardsDir) {
    if ($guardsDir && is_dir($guardsDir)) {
        File::deleteDirectory($guardsDir);
    }
    $guardsDir = null;
});

// ── File creation ─────────────────────────────────────────────────────────────

it('creates the config file at the correct path', function () use (&$guardsDir) {
    $this->artisan('permission:guard-config', ['guard' => 'api']);

    expect(file_exists($guardsDir.'/api.php'))->toBeTrue();
});

it('uses the guard argument as the filename', function () use (&$guardsDir) {
    $this->artisan('permission:guard-config', ['guard' => 'sanctum']);

    expect(file_exists($guardsDir.'/sanctum.php'))->toBeTrue();
    expect(file_exists($guardsDir.'/api.php'))->toBeFalse();
});

it('creates the directory when it does not exist yet', function () use (&$guardsDir) {
    expect(is_dir($guardsDir))->toBeFalse();

    $this->artisan('permission:guard-config', ['guard' => 'api']);

    expect(is_dir($guardsDir))->toBeTrue();
});

it('respects a custom guards_path config value', function () {
    $customSubdir = 'my-custom-guards-'.uniqid();
    config(['laravel-permission-generator.guards_path' => $customSubdir]);

    $this->artisan('permission:guard-config', ['guard' => 'api']);

    $path = app()->configPath($customSubdir.'/api.php');
    expect(file_exists($path))->toBeTrue();

    File::deleteDirectory(dirname($path));
});

// ── File content ──────────────────────────────────────────────────────────────

it('generates a valid PHP file that returns an array', function () use (&$guardsDir) {
    $this->artisan('permission:guard-config', ['guard' => 'api']);

    $content = require $guardsDir.'/api.php';

    expect($content)->toBeArray();
});

it('generated file contains all expected keys', function () use (&$guardsDir) {
    $this->artisan('permission:guard-config', ['guard' => 'api']);

    $content = require $guardsDir.'/api.php';

    expect($content)->toHaveKeys([
        'models',
        'except',
        'abilities',
        'custom_abilities',
        'other_permissions',
        'super_role',
    ]);
});

it('generated file ships with the seven default abilities', function () use (&$guardsDir) {
    $this->artisan('permission:guard-config', ['guard' => 'api']);

    $abilities = (require $guardsDir.'/api.php')['abilities'];

    expect($abilities)->toContain('view')
        ->toContain('view_any')
        ->toContain('create')
        ->toContain('update')
        ->toContain('restore')
        ->toContain('delete')
        ->toContain('force_delete');
});

it('generated file has empty models, except, custom_abilities, and other_permissions by default', function () use (&$guardsDir) {
    $this->artisan('permission:guard-config', ['guard' => 'api']);

    $content = require $guardsDir.'/api.php';

    expect($content['models'])->toBeEmpty();
    expect($content['except'])->toBeEmpty();
    expect($content['custom_abilities'])->toBeEmpty();
    expect($content['other_permissions'])->toBeEmpty();
});

it('generated file has admin as the default super_role', function () use (&$guardsDir) {
    $this->artisan('permission:guard-config', ['guard' => 'api']);

    expect((require $guardsDir.'/api.php')['super_role'])->toBe('admin');
});

it('file content matches the stub exactly', function () use (&$guardsDir) {
    $this->artisan('permission:guard-config', ['guard' => 'api']);

    $stubPath = realpath(__DIR__.'/../../stubs/guard.config.stub');
    $generated = file_get_contents($guardsDir.'/api.php');
    $stub = file_get_contents($stubPath);

    expect($generated)->toBe($stub);
});

// ── Already-exists guard ──────────────────────────────────────────────────────

it('does not overwrite an existing file', function () use (&$guardsDir) {
    mkdir($guardsDir, 0777, true);
    file_put_contents($guardsDir.'/api.php', '<?php return ["original"];');

    $this->artisan('permission:guard-config', ['guard' => 'api']);

    expect(file_get_contents($guardsDir.'/api.php'))->toBe('<?php return ["original"];');
});

it('outputs an already-exists error when the file is present', function () use (&$guardsDir) {
    mkdir($guardsDir, 0777, true);
    file_put_contents($guardsDir.'/api.php', '<?php return [];');

    $this->artisan('permission:guard-config', ['guard' => 'api'])
        ->expectsOutputToContain('already exists');
});

// ── Exit codes & output ───────────────────────────────────────────────────────

it('returns a successful exit code when the file is created', function () {
    $this->artisan('permission:guard-config', ['guard' => 'api'])
        ->assertExitCode(0);
});

it('outputs a created-successfully message', function () {
    $this->artisan('permission:guard-config', ['guard' => 'api'])
        ->expectsOutputToContain('created successfully');
});
