<?php

declare(strict_types=1);

namespace App\Modules\Core\Models;

use Database\Factories\MallFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tenant root. Intentionally NOT scoped by MallScope.
 *
 * @property int $id
 * @property string $name
 * @property string $type
 * @property string|null $subdomain
 * @property array<string,mixed>|null $settings
 */
class Mall extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'subdomain', 'settings'];

    protected $casts = ['settings' => 'encrypted:array'];

    public function floors(): HasMany
    {
        return $this->hasMany(Floor::class);
    }

    /**
     * Read a per-mall configuration value (dot-notation) from the settings JSON.
     * The base for per-mall config: gateway choice, feature flags, locale, hours.
     */
    public function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    protected static function newFactory(): Factory
    {
        return MallFactory::new();
    }
}
