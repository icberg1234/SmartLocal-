<?php

declare(strict_types=1);

namespace App\Modules\Core\Support;

use App\Modules\Core\Models\Mall;

/**
 * Holds the resolved tenant (mall) for the current request/job lifecycle.
 * Registered as a singleton by ModuleServiceProvider. Also the entry point
 * for per-mall configuration (base data) stored in malls.settings.
 */
final class CurrentMall
{
    private ?int $id = null;

    private ?Mall $model = null;

    private bool $loaded = false;

    public function set(?int $id): void
    {
        $this->id = $id;
        $this->model = null;
        $this->loaded = false;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function isResolved(): bool
    {
        return $this->id !== null;
    }

    public function model(): ?Mall
    {
        if (! $this->loaded) {
            $this->model = $this->id !== null ? Mall::query()->find($this->id) : null;
            $this->loaded = true;
        }

        return $this->model;
    }

    /**
     * Per-mall configuration value (dot-notation, e.g. 'sms.driver'),
     * falling back to $default when unset or when no mall is resolved.
     */
    public function setting(string $key, mixed $default = null): mixed
    {
        $mall = $this->model();

        return $mall !== null ? $mall->setting($key, $default) : $default;
    }
}
