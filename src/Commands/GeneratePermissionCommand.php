<?php

namespace Abather\LaravelPermissionGenerator\Commands;

use Abather\LaravelPermissionGenerator\PermissionGenerator;
use Illuminate\Console\Command;
use Spatie\Permission\Contracts\Permission as PermissionContract;
use Spatie\Permission\Contracts\Role as RoleContract;

class GeneratePermissionCommand extends Command
{
    protected $signature = 'permission:generate-permission';

    protected $description = 'Generate permissions from config file';

    protected int $guards = 0;

    protected int $models = 0;

    protected int $permissions = 0;

    protected int $exists = 0;

    protected int $created = 0;

    protected $permissionClass;

    protected $roleClass;

    public function handle(): int
    {
        $this->setPermissionClass();
        $this->setRoleClass();
        $this->loadFileGuards();

        foreach ($this->getGuards() as $guard) {
            foreach ($this->getGuardModels($guard) as $model) {
                $abilities = array_merge($this->getModelAbilities($guard, $model), $this->getCustomAbilities($guard, $model));

                foreach ($abilities as $ability) {
                    $this->generatePermission($guard, $this->getModelPermissionName($model, $ability));
                    $this->permissions++;
                }

                $this->models++;
            }

            foreach ($this->getGuardOtherPermissions($guard) as $permission) {
                $this->generatePermission($guard, $permission);
                $this->permissions++;
            }

            $this->grantPermissionToSuperRole($guard);

            $this->guards++;
        }

        $this->info("There are $this->models Models, for  $this->guards guards");
        $this->info("There are $this->permissions Permissions, $this->exists Permissions Exists and $this->created Permissions Created");
        $this->call('permission:cache-reset');

        return self::SUCCESS;
    }

    private function setPermissionClass(): void
    {
        $this->permissionClass = app(PermissionContract::class);
    }

    private function getPermissionClass()
    {
        return $this->permissionClass;
    }

    private function setRoleClass(): void
    {
        $this->roleClass = app(RoleContract::class);
    }

    private function getRoleClass()
    {
        return $this->roleClass;
    }

    private function loadFileGuards(): void
    {
        $guardsPath = config('laravel-permission-generator.guards_path', 'laravelPermissionGaurds');
        $path = $this->laravel->configPath($guardsPath);

        if (! is_dir($path)) {
            return;
        }

        foreach (glob($path.'/*.php') as $file) {
            $guard = pathinfo($file, PATHINFO_FILENAME);

            if (! config()->has("laravel-permission-generator.guards.$guard")) {
                config(["laravel-permission-generator.guards.$guard" => require $file]);
            }
        }
    }

    private function getConfigValue(string $key, mixed $default = []): mixed
    {
        return config("laravel-permission-generator.$key", $default);
    }

    private function getGuards(): array
    {
        return array_keys($this->getConfigValue('guards'));
    }

    private function getGuardModels($guard): array
    {
        $models = array_diff($this->getConfigValue('models'), $this->getConfigValue("guards.$guard.except"));

        return array_unique(array_merge($models, $this->getConfigValue("guards.$guard.models")));
    }

    private function getGuardAbilities($guard): array
    {
        return $this->getConfigValue("guards.$guard.abilities");
    }

    private function getModelAbilities($guard, $model): array
    {
        return $model::$abilities[$guard] ?? $this->getGuardAbilities($guard);
    }

    private function getCustomAbilities($guard, $model): array
    {
        return $model::$custom_abilities[$guard] ?? $this->getConfigValue("guards.$guard.custom_abilities.$model");
    }

    private function getGuardOtherPermissions($guard): array
    {
        return $this->getConfigValue("guards.$guard.other_permissions");
    }

    private function getGuardSuperRole($guard): ?string
    {
        return $this->getConfigValue("guards.$guard.super_role", null);
    }

    private function getModelPermissionName($model, $ability): string
    {
        return PermissionGenerator::make($model, $ability)->permission();
    }

    private function generatePermission($guard, $permission): void
    {
        $permission = $this->getPermissionClass()::findOrCreate($permission, $guard);

        if ($permission->wasRecentlyCreated) {
            $this->info("Permission $permission->name created for $guard");
            $this->created++;
        } else {
            $this->line("Permission $permission->name already exists for $guard");
            $this->exists++;
        }
    }

    private function grantPermissionToSuperRole($guard): void
    {
        $super_role = $this->getGuardSuperRole($guard);

        if ($super_role) {
            $role = $this->getRoleClass()::firstOrCreate(['name' => $super_role, 'guard_name' => $guard]);
            $role->givePermissionTo($this->getPermissionClass()::where('guard_name', $guard)->pluck('name')->all());
            $this->info("All $guard guard permissions granted to $role->name");
        } else {
            $this->error("$guard doesn't have super role");
        }
    }
}
