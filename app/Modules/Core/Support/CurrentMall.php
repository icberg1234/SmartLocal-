<?php

declare(strict_types=1);

namespace App\Modules\Core\Support;

/**
 * Holds the resolved tenant (mall) for the current request/job lifecycle.
 * Registered as a singleton by ModuleServiceProvider.
 */
final class CurrentMall
{
    private ?int $id = null;

    public function set(?int $id): void
    {
        $this->id = $id;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function isResolved(): bool
    {
        return $this->id !== null;
    }
}
