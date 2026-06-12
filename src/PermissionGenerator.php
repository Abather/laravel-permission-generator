<?php

namespace Abather\LaravelPermissionGenerator;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;

class PermissionGenerator
{
    private static ?array $namingConfig = null;

    private ?string $resolved = null;

    /** @param class-string $model */
    public function __construct(protected string $model, protected string $ability) {}

    /** @param class-string $model */
    public static function make(string $model, string $ability): static
    {
        return new self($model, $ability);
    }

    public function permission(): string
    {
        return $this->resolved ??= $this->buildPermission();
    }

    private function buildPermission(): string
    {
        $model = $this->getModelName();
        $separator = $this->getModelAbilitySeparator();
        $ability = $this->getAbilityName();

        return $this->getConfigValue('model_name_position', 'before') === 'after'
            ? "{$ability}{$separator}{$model}"
            : "{$model}{$separator}{$ability}";
    }

    public function hasPermission(?Authenticatable $user): bool
    {
        return $user?->can($this->permission()) ?? false;
    }

    /** @param class-string $model */
    public static function check(string $model, string $ability, ?Authenticatable $user): bool
    {
        return static::make($model, $ability)->hasPermission($user);
    }

    public function ability(string $ability): static
    {
        $this->ability = $ability;
        $this->resolved = null;

        return $this;
    }

    private function getConfigValue(string $key, mixed $default = null): mixed
    {
        self::$namingConfig ??= config('laravel-permission-generator.naming', []);

        return self::$namingConfig[$key] ?? $default;
    }

    private function resolveCase(string $case): string
    {
        return match ($case) {
            'camel', 'camelCase' => 'camel',
            'studly', 'studlyCase' => 'studly',
            default => 'snake',
        };
    }

    private function getModelName(): string
    {
        if (! $this->getConfigValue('use_model_class_base_name', true)) {
            return $this->model;
        }

        $case = $this->resolveCase($this->getConfigValue('model_name_case', 'snake'));

        return Str::$case(class_basename($this->model));
    }

    private function getAbilityName(): string
    {
        $case = $this->resolveCase($this->getConfigValue('ability_name_case', 'snake'));

        return Str::$case($this->ability);
    }

    private function getModelAbilitySeparator(): string
    {
        return match ($separator = $this->getConfigValue('model_ability_separator', '.')) {
            'space' => ' ',
            'comma' => ',',
            'dot', 'full_stop' => '.',
            'pipe' => '|',
            'colon' => ':',
            'semicolon' => ';',
            'arrow' => '→',
            default => $separator,
        };
    }
}
